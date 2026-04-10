<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerationService
{
    /**
     * Generate SQL DDL from a prompt by calling an LLM, save it, and execute it.
     *
     * @return array{runId:string, sql:string} The run id and SQL generated
     *
     * @throws \Exception
     */
    public function generate(string $prompt, ?int $userId = null, ?string $userEmail = null): array
    {
        $runId = uniqid('api_');
        $defaultConn = config('database.default');
        $credentials = [
            'username' => env('DB_USERNAME', config("database.connections.{$defaultConn}.username") ?? ''),
            'password' => env('DB_PASSWORD', config("database.connections.{$defaultConn}.password") ?? ''),
        ];
        $mainSchema = env('DB_MAIN_SCHEMA', 'autospec_main');
        $generatedSchema = $this->createGeneratedSchemaName($runId, $userId);
        $historyId = null;

        $this->ensureMainSchemaStructures($mainSchema);
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', str_replace('"', '""', $generatedSchema)));
        $historyId = $this->insertGenerationHistory($mainSchema, [
            'run_id' => $runId,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'schema_name' => $generatedSchema,
            'prompt' => $prompt,
            'status' => 'running',
            'error_message' => null,
        ]);

        $system = <<<'SYS'
You are a highly strict Database Schema Generator that outputs PURE, VALID JSON ONLY.

MANDATORY OUTPUT FORMAT:

You MUST respond with ONLY valid JSON (no markdown, no explanation, no code fences, no extra text).

The JSON structure MUST be:
{
  "tables": [
    {
      "name": "table_name",
      "columns": [
        {
          "name": "column_name",
          "type": "SQL_TYPE",
          "primary_key": boolean,
          "auto_increment": boolean,
          "not_null": boolean,
          "unique": boolean,
          "default": null_or_string,
          "foreign_key": null_or_{"table": "target_table", "column": "target_column"}
        }
      ],
      "dummy_data": [
        {"column1": value1, "column2": value2}
      ]
    }
  ],
  "triggers": [
    {
      "name": "trigger_name",
      "event": "AFTER INSERT|AFTER UPDATE",
      "table": "table_name",
      "statement": "SQL statement"
    }
  ],
  "functions": [
    {
      "name": "function_name",
      "parameters": [{"name": "param_name", "type": "INTEGER"}],
      "returns": "INTEGER",
      "statement": "SQL function body"
    }
  ],
  "stored_procedures": []
}

COLUMN TYPE EXAMPLES: INTEGER, VARCHAR(50), VARCHAR(100), TEXT, DATETIME, DECIMAL(10,2), BOOLEAN

RULES:
1. Each table MUST have at least one column
2. Provide 2-3 rows of realistic dummy_data for setiap table
3. Use actual SQL data types (VARCHAR, INTEGER, DATETIME, etc.)
4. Mark primary keys with "primary_key": true
5. Use auto_increment: true untuk ID columns yang SERIAL/BIGSERIAL
6. Use foreign_key untuk relationship antar tabel
7. Include triggers untuk business logic automation
8. Include functions untuk complex queries atau validations
9. Provide at least 1 trigger atau function jika applicable

Requirements:
Buatkan skema database untuk sistem: [NAMA ATAU JENIS APLIKASI]

Cakupan entitas yang dibutuhkan:
1. Aktor/Pengguna (Entities yang melakukan aksi)
2. Master Data (Data utama yang jarang berubah)
3. Transaksional/Aktivitas (Data operasional yang terus bertambah)

Spesifikasi Logika Bisnis:
- Triggers untuk automasi
- Functions untuk validasi atau complex logic
- Dummy data dalam bahasa Indonesia untuk realism
SYS;

        $llmUrl = env('LLM_API_URL');
        $llmKey = env('LLM_API_KEY');
        $llmModel = env('LLM_MODEL');

        if (empty($llmUrl)) {
            Log::warning('LLM_API_URL not configured; aborting generation.');
            throw new \RuntimeException('LLM_API_URL not set');
        }

        $client = new Client(['base_uri' => $llmUrl, 'timeout' => 30]);

        // Filenames (use unique id per run)
        $sqlPath = "generations/{$runId}.sql";
        $openapiPath = "generations/{$runId}.openapi.json";
        $postmanPath = "generations/{$runId}.postman.json";

        try {
            $headers = ['Accept' => 'application/json'];
            if (! empty($llmKey)) {
                $headers['Authorization'] = 'Bearer '.$llmKey;
            }

            $payload = [
                'model' => $llmModel,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ];

            $response = $client->post('', [
                'json' => $payload,
                'headers' => $headers,
            ]);

            $body = (string) $response->getBody();

            // Extract the LLM content from common shapes, fallback to raw body
            $raw = $this->extractLlmContent($body);

            // Persist raw LLM output for post-mortem debugging
            try {
                Storage::disk('local')->put("generations/{$runId}.raw.txt", $body);
            } catch (\Throwable $__) {
                // ignore write errors
            }

            // Clean the raw LLM output from markdown fences and noise
            $cleanJson = $this->cleanLlmJson($raw);

            // Validate and decode JSON according to strict schema
            $schema = [];
            $parsedFallback = [];
            $schemaWasSalvaged = false;
            try {
                $schema = $this->validateAndDecodeJson($cleanJson);
            } catch (\RuntimeException $e) {
                // First attempt failed — try a tolerant cleanup to salvage common LLM formatting issues
                Log::warning('Initial JSON decoding failed, attempting tolerant cleanup: '.$e->getMessage());
                Log::debug('Raw LLM output (truncated): '.substr($raw, 0, 2000));

                $tolerant = $cleanJson;
                // Remove trailing commas before object/array close
                $tolerant = preg_replace('/,\s*([}\]])/', '$1', $tolerant);
                // Replace single quotes for keys/strings with double quotes when safe-ish
                $tolerant = preg_replace_callback('/(\{|,|\[)\s*\'([^\']+)\'\s*:/', function ($m) {
                    return $m[1].'"'.str_replace('"', '\\"', $m[2]).'":';
                }, $tolerant);
                // Replace single-quoted string values to double quotes when clearly delimited
                $tolerant = preg_replace_callback('/:\s*\'([^\']*)\'([,}\]])/', function ($m) {
                    return ': "'.str_replace('"', '\\"', $m[1]).'"'.$m[2];
                }, $tolerant);

                try {
                    $schema = $this->validateAndDecodeJson($tolerant);
                } catch (\RuntimeException $e2) {
                    // Log both cleaned variants for debugging
                    Log::error('Tolerant JSON decoding also failed. Cleaned: '.substr($cleanJson, 0, 2000));
                    Log::error('Tolerant JSON attempt: '.substr($tolerant, 0, 2000));

                    // As a fallback, try to salvage a valid JSON prefix for the schema,
                    // then pair it with SQL-like content extracted from the raw text.
                    $parsedFallback = $this->parseSqlFromRaw($raw);

                    $tablesSection = $this->extractJsonArraySection($tolerant, 'tables');
                    if ($tablesSection !== null) {
                        $decodedPrefix = json_decode('{"tables":'.$tablesSection.'}', true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPrefix)) {
                            $schema = $decodedPrefix;
                            $schemaWasSalvaged = true;

                            if (! empty($parsedFallback['dcl'])) {
                                $fallbackDcl = array_values(array_filter(array_map('trim', preg_split('/\r?\n+/', $parsedFallback['dcl']))));
                                if (! empty($fallbackDcl)) {
                                    $schema['dcl'] = array_values(array_unique(array_merge($schema['dcl'] ?? [], $fallbackDcl)));
                                }
                            }

                            // Continue with the salvaged schema so DDL/DML can still be generated.
                        } else {
                            // Persist salvaged SQL for manual inspection
                            try {
                                Storage::disk('local')->put("generations/{$runId}.salvaged.sql", $parsedFallback['full'] ?? $raw);
                            } catch (\Throwable $__) {
                                // ignore
                            }

                            // Return a structured fallback so frontend can still display SQL
                            return [
                                'runId' => $runId,
                                'generatedSql' => [
                                    'ddl' => $parsedFallback['ddl'] ?? '',
                                    'dcl' => $parsedFallback['dcl'] ?? '',
                                    'dml' => $parsedFallback['dml'] ?? '',
                                    'functions' => $parsedFallback['functions'] ?? '',
                                    'stored_procedures' => $parsedFallback['stored_procedures'] ?? '',
                                    'triggers' => $parsedFallback['triggers'] ?? '',
                                    'trigger' => $parsedFallback['trigger'] ?? '',
                                ],
                                'schemaOverview' => [
                                    'tables' => [],
                                    'credentials' => $credentials,
                                    'downloads' => [
                                        'database.sql' => false,
                                        'openapi.json' => false,
                                        'postman_collection.json' => false,
                                    ],
                                    'files' => [
                                        'database.sql' => $parsedFallback['ddl'] ?? '',
                                        'openapi.json' => '',
                                        'postman_collection.json' => '',
                                    ],
                                ],
                                'credentials' => $credentials,
                                'error' => 'LLM JSON parse failed: '.$e2->getMessage(),
                            ];
                        }
                    } else {
                        // Persist salvaged SQL for manual inspection
                        try {
                            Storage::disk('local')->put("generations/{$runId}.salvaged.sql", $parsedFallback['full'] ?? $raw);
                        } catch (\Throwable $__) {
                            // ignore
                        }

                        // Return a structured fallback so frontend can still display SQL
                        return [
                            'runId' => $runId,
                            'generatedSql' => [
                                'ddl' => $parsedFallback['ddl'] ?? '',
                                'dcl' => $parsedFallback['dcl'] ?? '',
                                'dml' => $parsedFallback['dml'] ?? '',
                                'functions' => $parsedFallback['functions'] ?? '',
                                'stored_procedures' => $parsedFallback['stored_procedures'] ?? '',
                                'triggers' => $parsedFallback['triggers'] ?? '',
                                'trigger' => $parsedFallback['trigger'] ?? '',
                            ],
                            'schemaOverview' => [
                                'tables' => [],
                                'credentials' => $credentials,
                                'downloads' => [
                                    'database.sql' => false,
                                    'openapi.json' => false,
                                    'postman_collection.json' => false,
                                ],
                                'files' => [
                                    'database.sql' => $parsedFallback['ddl'] ?? '',
                                    'openapi.json' => '',
                                    'postman_collection.json' => '',
                                ],
                            ],
                            'credentials' => $credentials,
                            'error' => 'LLM JSON parse failed: '.$e2->getMessage(),
                        ];
                    }
                }
            }

            // Convert JSON schema to categorized SQL parts
            $categorizedSql = $this->llmJsonToSqlCategorized($schema);

            // Ensure all categorized parts are strings to avoid Array to string conversion warnings
            $ddlPart = '';
            $dmlPart = '';
            $triggerPart = '';
            $dclPart = '';

            if (isset($categorizedSql['ddl'])) {
                $ddlPart = is_string($categorizedSql['ddl'])
                    ? $categorizedSql['ddl']
                    : (is_array($categorizedSql['ddl']) ? implode("\n\n", $categorizedSql['ddl']) : json_encode($categorizedSql['ddl']));
            }
            if (isset($categorizedSql['dml'])) {
                $dmlPart = is_string($categorizedSql['dml'])
                    ? $categorizedSql['dml']
                    : (is_array($categorizedSql['dml']) ? implode("\n\n", $categorizedSql['dml']) : json_encode($categorizedSql['dml']));
            }
            if (isset($categorizedSql['trigger'])) {
                $triggerPart = is_string($categorizedSql['trigger'])
                    ? $categorizedSql['trigger']
                    : (is_array($categorizedSql['trigger']) ? implode("\n\n", $categorizedSql['trigger']) : json_encode($categorizedSql['trigger']));
            }
            if (isset($categorizedSql['dcl'])) {
                $dclPart = is_string($categorizedSql['dcl'])
                    ? $categorizedSql['dcl']
                    : (is_array($categorizedSql['dcl']) ? implode("\n\n", $categorizedSql['dcl']) : json_encode($categorizedSql['dcl']));
            }

            // Order: DDL, DCL, TRIGGER/FUNCTIONS, then DML
            $sql = trim(implode("\n\n", array_filter([trim($ddlPart), trim($dclPart), trim($triggerPart), trim($dmlPart)])));

            // Save SQL atomically
            $tmpSqlPath = $sqlPath.'.tmp';
            Storage::disk('local')->put($tmpSqlPath, $sql);
            Storage::disk('local')->move($tmpSqlPath, $sqlPath);

            // Normalize and fix common trigger/function/DCL issues then re-extract parts
            $fixedSql = $this->normalizeSqlIdentifiersAndDcl($sql);

            // Ensure EXECUTE FUNCTION ...) lines end with semicolon
            $fixedSql = preg_replace('/(EXECUTE\s+FUNCTION[^;\n]*\))(?!(\s*;))/i', '$1;', $fixedSql);
            // Ensure CREATE TRIGGER lines end with semicolon if missing
            $fixedSql = preg_replace('/(CREATE\s+(?:OR\s+REPLACE\s+)?TRIGGER[\s\S]*?EXECUTE\s+FUNCTION[^;\n]*)(?!(\s*;))/i', '$1;', $fixedSql);

            // Persist fixed SQL for inspection
            try {
                Storage::disk('local')->put("generations/{$runId}.fixed.sql", $fixedSql);
            } catch (\Throwable $__) {
                // ignore
            }

            // Sanitize SQL for execution: remove function/procedure/trigger blocks
            [$executableSql, $skipped] = $this->sanitizeSqlForExecution($sql);

            // Persist skipped (complex) definitions for manual review
            if (! empty($skipped)) {
                $skipPath = "generations/{$runId}.skipped.sql";
                Storage::disk('local')->put($skipPath, $skipped);
            }

            // Execute the full raw SQL (including functions/procedures/triggers) inside
            // a dedicated transaction so any error rolls everything back.
            $executionFailed = false;
            $executionErrorMsg = '';
            $transactionStarted = false;
            $fixedSql = $sql;
            try {
                DB::beginTransaction();
                $transactionStarted = true;

                // Normalize identifiers and DCL syntax to reduce common LLM mistakes
                $fixedSql = $this->normalizeSqlIdentifiersAndDcl($sql);

                // Scope execution to generated schema to avoid cross-schema contamination.
                // Use session-level setting so subsequent unprepared statements see the search_path.
                DB::statement("SELECT set_config('search_path', ?, false)", [$generatedSchema.',public']);

                // Persist the fixed SQL for debugging
                try {
                    Storage::disk('local')->put("generations/{$runId}.fixed.sql", $fixedSql);
                } catch (\Throwable $__) {
                    // ignore
                }

                // Use unprepared to allow multi-statement blocks (plpgsql $$...$$ etc.)
                // Ensure CREATE TABLE statements without explicit schema are qualified
                // so they are created in the generated schema rather than public.
                $fixedSql = preg_replace_callback(
                    '/\bCREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?("?)([a-zA-Z0-9_]+)("?)/i',
                    function ($m) use ($generatedSchema) {
                        $ifNot = isset($m[1]) ? $m[1] : '';
                        $tbl = $m[3];
                        return 'CREATE TABLE ' . ($ifNot ?: '') . '"' . str_replace('"', '""', $generatedSchema) . '"."' . $tbl . '"';
                    },
                    $fixedSql
                );

                DB::unprepared($fixedSql);

                DB::commit();
            } catch (\Throwable $e) {
                $executionFailed = true;
                $executionErrorMsg = $e->getMessage();

                // Roll back the inner transaction/savepoint only if it actually started.
                if ($transactionStarted) {
                    try {
                        DB::rollBack();
                    } catch (\Throwable $__) {
                        // ignore rollback errors
                    }
                }
                $transactionStarted = false;

                // Try to extract helpful location information from the DB error
                $origMsg = $e->getMessage();
                $lineInfo = '';

                if (preg_match('/LINE\s+(\d+)/i', $origMsg, $m)) {
                    $errLine = (int) $m[1];
                    $lines = preg_split('/\r?\n/', $sql);
                    $total = count($lines);
                    $start = max(1, $errLine - 3);
                    $end = min($total, $errLine + 3);
                    $ctx = [];
                    for ($i = $start; $i <= $end; $i++) {
                        $ctx[] = sprintf('%4d: %s', $i, $lines[$i - 1]);
                    }
                    $lineInfo = "\n--- SQL context (lines {$start}-{$end}) ---\n".implode("\n", $ctx)."\n";
                } elseif (preg_match('/position\s+(\d+)/i', $origMsg, $m2)) {
                    $pos = (int) $m2[1];
                    $prefix = substr($sql, 0, max(0, $pos));
                    $errLine = substr_count($prefix, "\n") + 1;
                    $lines = preg_split('/\r?\n/', $sql);
                    $total = count($lines);
                    $start = max(1, $errLine - 3);
                    $end = min($total, $errLine + 3);
                    $ctx = [];
                    for ($i = $start; $i <= $end; $i++) {
                        $ctx[] = sprintf('%4d: %s', $i, $lines[$i - 1]);
                    }
                    $lineInfo = "\n--- SQL context (approx position {$pos}, lines {$start}-{$end}) ---\n".implode("\n", $ctx)."\n";
                } else {
                    $snippet = substr($sql, 0, 800);
                    $lineInfo = "\n--- SQL snippet (first 800 chars) ---\n".$snippet."\n";
                }

                // Save the raw error and context for easier debugging
                try {
                    Storage::disk('local')->put("generations/{$runId}.error.txt", $origMsg."\n".$lineInfo);
                } catch (\Throwable $__) {
                    // ignore
                }

                // do not rethrow here; we'll return parsed SQL parts and include error info
            }

            // --- Tahap 2: Request OpenAPI JSON ---
            $groqKey = env('GROQ_API_KEY');
            $groqUrl = env('GROQ_API_URL', $llmUrl);

            if (! empty($groqUrl)) {
                $openapiSystem = <<<'SYS'
You are a converter that transforms a database schema into a strict OpenAPI 3.0.0 specification in pure JSON. Base URL: http://localhost:8000/rest/v1/. Output ONLY valid JSON that conforms to OpenAPI 3.0.0 — do not include any explanatory text or comments.
SYS;

                $resp = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->withToken($groqKey)->post($groqUrl, [
                    'model' => env('GROQ_MODEL', 'llama3-70b-8192'),
                    'messages' => [
                        ['role' => 'system', 'content' => $openapiSystem],
                        ['role' => 'user', 'content' => $sql],
                    ],
                ]);

                $body2 = (string) $resp->body();
                $cleanOpenapi = $this->cleanLlmJson($body2);
                $decoded = json_decode($cleanOpenapi, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('OpenAPI response is not valid JSON: '.json_last_error_msg());
                }

                $tmpOpenapi = $openapiPath.'.tmp';
                Storage::disk('local')->put($tmpOpenapi, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                Storage::disk('local')->move($tmpOpenapi, $openapiPath);
            } else {
                Log::warning('Groq URL not configured, skipping OpenAPI generation');
            }

            // --- Tahap 3: Request Postman Collection JSON ---
            if (! empty($groqUrl)) {
                $postmanSystem = <<<'SYS'
You are a generator that transforms a database schema into a Postman Collection v2.1.0 JSON file. Base URL: http://localhost:8000/rest/v1/. Output ONLY valid JSON conforming to Postman Collection v2.1.0. Include example Authorization header with 'Bearer <ACCESS_TOKEN>'. Do not include explanatory text.
SYS;

                $resp3 = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->withToken($groqKey)->post($groqUrl, [
                    'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
                    'messages' => [
                        ['role' => 'system', 'content' => $postmanSystem],
                        ['role' => 'user', 'content' => $sql],
                    ],
                ]);

                $body3 = (string) $resp3->body();
                $cleanPostman = $this->cleanLlmJson($body3);
                $decoded3 = json_decode($cleanPostman, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Postman response is not valid JSON: '.json_last_error_msg());
                }

                $tmpPostman = $postmanPath.'.tmp';
                Storage::disk('local')->put($tmpPostman, json_encode($decoded3, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                Storage::disk('local')->move($tmpPostman, $postmanPath);
            } else {
                Log::warning('Groq URL not configured, skipping Postman generation');
            }

            // All steps succeeded — commit transaction
            if ($transactionStarted) {
                DB::commit();
                $transactionStarted = false;
            }

            // Re-extract categorized parts from the fixed SQL so UI shows the same SQL that ran
            $parsedFixed = $this->parseSqlFromRaw($fixedSql);

            // Fall back to categorizedSql when parsing misses parts
            $retDdl = $parsedFixed['ddl'] ?? $categorizedSql['ddl'] ?? '';
            $retDcl = $parsedFixed['dcl'] ?? $categorizedSql['dcl'] ?? '';
            $retDml = $parsedFixed['dml'] ?? $categorizedSql['dml'] ?? '';
            $retTrigger = $parsedFixed['trigger'] ?? $categorizedSql['trigger'] ?? '';

            if (! empty($schemaWasSalvaged) && ! empty($parsedFallback)) {
                $retDdl = $retDdl !== '' ? $retDdl : ($parsedFallback['ddl'] ?? '');
                $retDcl = $retDcl !== '' ? $retDcl : ($parsedFallback['dcl'] ?? '');
                $retDml = $retDml !== '' ? $retDml : ($parsedFallback['dml'] ?? '');
                $retTrigger = $retTrigger !== '' ? $retTrigger : ($parsedFallback['trigger'] ?? '');
            }

            $result = [
                'runId' => $runId,
                'generatedSchema' => $generatedSchema,
                'generatedSql' => [
                    'ddl' => $retDdl,
                    'dcl' => $retDcl,
                    'dml' => $retDml,
                    'functions' => $parsedFixed['functions'] ?? $categorizedSql['functions'] ?? '',
                    'stored_procedures' => $parsedFixed['stored_procedures'] ?? $categorizedSql['stored_procedures'] ?? '',
                    'triggers' => $parsedFixed['triggers'] ?? $categorizedSql['triggers'] ?? '',
                    'trigger' => $retTrigger,
                ],
                'schemaOverview' => [
                    'tables' => $this->extractTableOverview($schema),
                    'generated_schema' => $generatedSchema,
                    'credentials' => $credentials,
                ],
                'credentials' => $credentials,
            ];

            $artifactFiles = [
                'database.sql' => $sqlPath,
                'openapi.json' => $openapiPath,
                'postman_collection.json' => $postmanPath,
            ];

            $downloads = [];
            $files = [];
            foreach ($artifactFiles as $filename => $path) {
                $exists = Storage::disk('local')->exists($path);
                $downloads[$filename] = $exists;
                $files[$filename] = $exists ? Storage::disk('local')->get($path) : '';
            }

            // Build comprehensive SQL file with all parts properly organized
            $comprehensiveSql = "-- ========================================\n";
            $comprehensiveSql .= "-- Database Schema Generation\n";
            $comprehensiveSql .= "-- Generated: ".date('Y-m-d H:i:s')."\n";
            $comprehensiveSql .= "-- Run ID: ".$runId."\n";
            $comprehensiveSql .= "-- Schema: ".$generatedSchema."\n";
            $comprehensiveSql .= "-- ========================================\n\n";

            if (!empty($retDdl)) {
                $comprehensiveSql .= "-- DDL: Table Definitions\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= $retDdl."\n\n";
            }

            if (!empty($retDcl)) {
                $comprehensiveSql .= "-- DCL: Access Control\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= $retDcl."\n\n";
            }

            if (!empty($parsedFixed['functions'] ?? $categorizedSql['functions'] ?? '')) {
                $comprehensiveSql .= "-- Functions\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= ($parsedFixed['functions'] ?? $categorizedSql['functions'] ?? '')."\n\n";
            }

            if (!empty($parsedFixed['stored_procedures'] ?? $categorizedSql['stored_procedures'] ?? '')) {
                $comprehensiveSql .= "-- Stored Procedures\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= ($parsedFixed['stored_procedures'] ?? $categorizedSql['stored_procedures'] ?? '')."\n\n";
            }

            if (!empty($parsedFixed['triggers'] ?? $categorizedSql['triggers'] ?? '')) {
                $comprehensiveSql .= "-- Triggers\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= ($parsedFixed['triggers'] ?? $categorizedSql['triggers'] ?? '')."\n\n";
            }

            if (!empty($retDml)) {
                $comprehensiveSql .= "-- DML: Dummy Data\n";
                $comprehensiveSql .= "-- ========================================\n";
                $comprehensiveSql .= $retDml."\n\n";
            }

            $result['downloads'] = $downloads;
            $result['files'] = $files;
            $result['files']['database.sql'] = $comprehensiveSql;
            // Ensure database.sql is marked as available since we're generating it
            $result['downloads']['database.sql'] = true;
            
            $result['schemaOverview']['downloads'] = $result['downloads'];
            $result['schemaOverview']['files'] = [
                'database.sql' => $comprehensiveSql,
                'openapi.json' => $files['openapi.json'] ?? '',
                'postman_collection.json' => $files['postman_collection.json'] ?? '',
            ];

            if (! empty($executionFailed)) {
                $result['error'] = 'SQL Execution skipped/failed: '.($executionErrorMsg ?? 'unknown').'. See generations/'.$runId.'.error.txt';
            }

            $this->updateGenerationHistory($mainSchema, $historyId, [
                'status' => $executionFailed ? 'failed' : 'success',
                'error_message' => $executionFailed ? $executionErrorMsg : null,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->updateGenerationHistory($mainSchema, $historyId, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Roll back DB and clean up any partial artifacts
            try {
                DB::rollBack();
            } catch (\Throwable $__) {
                // ignore
            }

            // Attempt to remove any created files
            foreach ([$sqlPath, $openapiPath, $postmanPath] as $p) {
                try {
                    if (Storage::disk('local')->exists($p)) {
                        Storage::disk('local')->delete($p);
                    }
                } catch (\Throwable $__) {
                    // ignore
                }
            }

            Log::error('GenerationService failed: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Try to extract SQL parts from raw LLM output when JSON parsing fails.
     * Returns array with keys: full, ddl, dml, dcl, functions, stored_procedures, triggers, trigger
     */
    private function parseSqlFromRaw(string $raw): array
    {
        $out = [
            'full' => $raw,
            'ddl' => '',
            'dml' => '',
            'dcl' => '',
            'functions' => '',
            'stored_procedures' => '',
            'triggers' => '',
            'trigger' => '',
        ];

        // Remove markdown fences
        $clean = preg_replace('/^```[a-zA-Z0-9]*\n|\n```$/', '', trim($raw));

        // Regex patterns (more specific)
        $createTable = '/\bCREATE\s+TABLE\b[\s\S]*?;/i';
        $insert = '/\bINSERT\s+INTO\b[\s\S]*?;/i';
        $dcl = '/\b(?:GRANT|REVOKE)\b[\s\S]*?;/i';
        $functionDollar = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?FUNCTION\b[\s\S]*?\$\$[\s\S]*?\$\$\s*;?/i';
        $procedureDollar = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?PROCEDURE\b[\s\S]*?\$\$[\s\S]*?\$\$\s*;?/i';
        $functionSimple = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?FUNCTION\b[\s\S]*?;/i';
        $procedureSimple = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?PROCEDURE\b[\s\S]*?;/i';
        $trigger = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?TRIGGER\b[\s\S]*?;/i';

        // 1) Extract DDL (CREATE TABLE) and DCL, TRIGGER/FUNCTION definitions from the original clean text
        preg_match_all($createTable, $clean, $mTables);
        preg_match_all($dcl, $clean, $mDcl);
        preg_match_all($trigger, $clean, $mTrigger);
        preg_match_all($functionDollar, $clean, $mFunctionDollar);
        preg_match_all($procedureDollar, $clean, $mProcedureDollar);
        preg_match_all($functionSimple, $clean, $mFunctionSimple);
        preg_match_all($procedureSimple, $clean, $mProcedureSimple);

        $out['ddl'] = ! empty($mTables[0]) ? implode("\n\n", array_map('trim', $mTables[0])) : '';
        $out['dcl'] = ! empty($mDcl[0]) ? implode("\n\n", array_map('trim', $mDcl[0])) : '';

        // Build trigger collection: include full function/procedure definitions and triggers
        $functions = [];
        if (! empty($mFunctionDollar[0])) {
            $functions = array_merge($functions, $mFunctionDollar[0]);
        }
        if (! empty($mFunctionSimple[0])) {
            foreach ($mFunctionSimple[0] as $f) {
                $add = true;
                foreach ($functions as $ex) {
                    if (stripos($ex, substr($f, 0, 40)) !== false) {
                        $add = false;
                        break;
                    }
                }
                if ($add) {
                    $functions[] = $f;
                }
            }
        }

        $procedures = [];
        if (! empty($mProcedureDollar[0])) {
            $procedures = array_merge($procedures, $mProcedureDollar[0]);
        }
        if (! empty($mProcedureSimple[0])) {
            foreach ($mProcedureSimple[0] as $p) {
                $add = true;
                foreach ($procedures as $ex) {
                    if (stripos($ex, substr($p, 0, 40)) !== false) {
                        $add = false;
                        break;
                    }
                }
                if ($add) {
                    $procedures[] = $p;
                }
            }
        }

        $trigs = [];
        if (! empty($mTrigger[0])) {
            $trigs = array_merge($trigs, $mTrigger[0]);
        }

        $out['functions'] = implode("\n\n", array_map('trim', $functions));
        $out['stored_procedures'] = implode("\n\n", array_map('trim', $procedures));
        $out['triggers'] = implode("\n\n", array_map('trim', $trigs));
        $out['trigger'] = implode("\n\n", array_filter([$out['functions'], $out['stored_procedures'], $out['triggers']]));

        // 2) Mask out function/trigger bodies so INSERT detection ignores INSERTs inside procedural code
        $masked = $clean;
        $allDefs = [];
        // Collect all procedural/trigger matches with offsets
        preg_match_all($functionDollar, $clean, $m1, PREG_OFFSET_CAPTURE);
        preg_match_all($procedureDollar, $clean, $m2, PREG_OFFSET_CAPTURE);
        preg_match_all($functionSimple, $clean, $m3, PREG_OFFSET_CAPTURE);
        preg_match_all($procedureSimple, $clean, $m4, PREG_OFFSET_CAPTURE);
        preg_match_all($trigger, $clean, $m5, PREG_OFFSET_CAPTURE);

        $matches = [];
        if (! empty($m1[0])) {
            $matches = array_merge($matches, $m1[0]);
        }
        if (! empty($m2[0])) {
            $matches = array_merge($matches, $m2[0]);
        }
        if (! empty($m3[0])) {
            $matches = array_merge($matches, $m3[0]);
        }
        if (! empty($m4[0])) {
            $matches = array_merge($matches, $m4[0]);
        }
        if (! empty($m5[0])) {
            $matches = array_merge($matches, $m5[0]);
        }

        // Sort by offset descending to safely replace substrings
        usort($matches, function ($a, $b) {
            return $b[1] <=> $a[1];
        });
        foreach ($matches as $mc) {
            $text = $mc[0];
            $off = $mc[1];
            if ($off === false) {
                continue;
            }
            $len = strlen($text);
            // Replace the procedural block with spaces to keep offsets stable
            $masked = substr_replace($masked, str_repeat(' ', $len), $off, $len);
        }

        // 3) Extract INSERTs only from masked content (so those inside functions/triggers are ignored)
        preg_match_all($insert, $masked, $mInserts);
        $out['dml'] = ! empty($mInserts[0]) ? implode("\n\n", array_map('trim', $mInserts[0])) : '';

        // Fallback: if DDL or DML still empty, attempt statement-splitting classification
        if (trim($out['ddl']) === '' || trim($out['dml']) === '') {
            // Split by semicolon first (preserve text without semicolons later)
            $parts = preg_split('/;\s*(\r?\n)?/m', $clean);

            foreach ($parts as $p) {
                $s = trim($p);
                if ($s === '') {
                    continue;
                }

                $low = strtolower($s);
                if (preg_match('/^create\s+table\b/i', $s)) {
                    $candidate = $s;
                    // make sure it ends with ); style
                    if (! str_ends_with(trim($candidate), ';')) {
                        $candidate = trim($candidate).';';
                    }
                    if (trim($out['ddl']) === '') {
                        $out['ddl'] = $candidate;
                    } else {
                        $out['ddl'] .= "\n\n".$candidate;
                    }

                    continue;
                }

                if (preg_match('/^insert\s+into\b/i', $s)) {
                    // ensure INSERTs inside functions are ignored by checking masked version
                    // if the same substring exists in masked, accept it
                    if (strpos($masked, $s) !== false) {
                        $candidate = $s;
                        if (! str_ends_with(trim($candidate), ';')) {
                            $candidate = trim($candidate).';';
                        }
                        if (trim($out['dml']) === '') {
                            $out['dml'] = $candidate;
                        } else {
                            $out['dml'] .= "\n\n".$candidate;
                        }
                    }

                    continue;
                }

                if (preg_match('/^\s*(grant|revoke)\b/i', $s)) {
                    $candidate = $s;
                    if (! str_ends_with(trim($candidate), ';')) {
                        $candidate = trim($candidate).';';
                    }
                    if (trim($out['dcl']) === '') {
                        $out['dcl'] = $candidate;
                    } else {
                        $out['dcl'] .= "\n\n".$candidate;
                    }

                    continue;
                }

                if (preg_match('/^create\s+(or\s+replace\s+)?function\b/i', $s)) {
                    $candidate = $s;
                    if (! str_ends_with(trim($candidate), ';')) {
                        $candidate = trim($candidate).';';
                    }
                    if (trim($out['functions']) === '') {
                        $out['functions'] = $candidate;
                    } else {
                        $out['functions'] .= "\n\n".$candidate;
                    }

                    continue;
                }

                if (preg_match('/^create\s+(or\s+replace\s+)?procedure\b/i', $s)) {
                    $candidate = $s;
                    if (! str_ends_with(trim($candidate), ';')) {
                        $candidate = trim($candidate).';';
                    }
                    if (trim($out['stored_procedures']) === '') {
                        $out['stored_procedures'] = $candidate;
                    } else {
                        $out['stored_procedures'] .= "\n\n".$candidate;
                    }

                    continue;
                }

                if (preg_match('/^create\s+(or\s+replace\s+)?trigger\b/i', $s)) {
                    $candidate = $s;
                    if (! str_ends_with(trim($candidate), ';')) {
                        $candidate = trim($candidate).';';
                    }
                    if (trim($out['triggers']) === '') {
                        $out['triggers'] = $candidate;
                    } else {
                        $out['triggers'] .= "\n\n".$candidate;
                    }

                    continue;
                }
            }
        }

        $out['trigger'] = implode("\n\n", array_filter([$out['functions'], $out['stored_procedures'], $out['triggers']]));

        // Final fallback: if everything still empty, keep whole cleaned input as ddl so UI shows something
        if (trim($out['ddl']) === '' && trim($out['dml']) === '' && trim($out['trigger']) === '' && trim($out['dcl']) === '') {
            $out['ddl'] = trim($clean);
        }

        return $out;
    }

    /**
     * Normalize identifiers that contain hyphens and fix common DCL patterns produced by LLMs.
     * Only replaces hyphens in identifiers outside of single-quoted literals.
     */
    private function normalizeSqlIdentifiersAndDcl(string $sql): string
    {
        // Helper: check if a position is inside single quotes (naive but practical)
        $isInQuotes = function (string $s, int $pos) {
            $sub = substr($s, 0, $pos);
            // Count single quotes
            $count = substr_count($sub, "'");

            return ($count % 2) === 1;
        };

        // 1) Replace hyphenated identifiers like api_x-y with api_x_y, but only when
        // the identifier starts with a letter or underscore (avoid dates).
        // Special-case: LLM sometimes outputs quoted ALL in DCL: "ALL" TABLES
        $sql = preg_replace('/ON\s+"?ALL"?\s+TABLES\s+IN\s+SCHEMA\s+([A-Za-z_][A-Za-z0-9_]*)/i', 'ON ALL TABLES IN SCHEMA $1', $sql);

        $sql = preg_replace_callback(
            '/\b([A-Za-z_][A-Za-z0-9_]*)-([A-Za-z_][A-Za-z0-9_]*)\b/',
            function ($m) {
                $full = $m[0];
                $pos = strpos($GLOBALS['__gen_sql_tmp__'] ?? $m[0], $m[0]);

                // The callback doesn't know absolute position; we'll instead perform a safer replace below.
                return $m[1].'_'.$m[2];
            },
            $sql
        );

        // Safer global replace for runId-based hyphen patterns (common case)
        // Replace occurrences like api_<id>-something to api_<id>_something
        $sql = preg_replace('/(api_[0-9a-f]+)-([A-Za-z_][A-Za-z0-9_]*)/i', '$1_$2', $sql);

        // 2) Fix DCL patterns like: ON <ident> IN SCHEMA <schema>
        // Transform to: ON TABLE <schema>."<ident>"
        $sql = preg_replace_callback('/\bON\s+([A-Za-z_][A-Za-z0-9_\.\"]*)\s+IN\s+SCHEMA\s+([A-Za-z_][A-Za-z0-9_]*)/i', function ($m) {
            $ident = trim($m[1], " \t\n\r\"'");
            $schema = $m[2];
            // Quote identifier to be safe
            $identQuoted = '"'.str_replace('"', '""', $ident).'"';

            return 'ON TABLE '.$schema.'.'.$identQuoted;
        }, $sql);

        // 3) Ensure any remaining GRANT/REVOKE that reference unquoted identifiers are quoted
        $sql = preg_replace_callback('/\b(GRANT|REVOKE)\b([\s\S]{1,80}?);/i', function ($m) {
            $stmt = $m[0];
            // Quote bare identifiers after ON if they look like identifiers
            $stmt = preg_replace_callback('/\bON\s+([A-Za-z_][A-Za-z0-9_\.-]*)/i', function ($n) {
                $ident = $n[1];
                $ident2 = str_replace('-', '_', $ident);
                if (strpos($ident2, '.') !== false) {
                    // schema.table
                    $parts = explode('.', $ident2, 2);

                    return 'ON '.$parts[0].'."'.$parts[1].'"';
                }

                return 'ON "'.$ident2.'"';
            }, $stmt);

            return $stmt;
        }, $sql);

        // 4) Fix common incorrect trigger variable names produced by LLMs
        $sql = str_ireplace([
            'TGTableName',
            'TGTABLENAME',
            'TG_OP_NAME',
            'TGOPNAME',
        ], [
            'TG_TABLE_NAME',
            'TG_TABLE_NAME',
            'TG_OP',
            'TG_OP',
        ], $sql);

        return $sql;
    }

    /**
     * Remove function/procedure/trigger definitions from generated SQL so we don't execute
     * potentially unsupported or unsafe procedural code. Returns [executableSql, skippedBlocks].
     */
    private function sanitizeSqlForExecution(string $sql): array
    {
        $skipped = [];

        // 1) Remove $$-quoted function/procedure bodies: CREATE ... $$ ... $$;
        $patternDollars = '/(CREATE\s+(OR\s+REPLACE\s+)?(FUNCTION|PROCEDURE)\b.*?\$\$.*?\$\$\s*;)/is';
        $sql = preg_replace_callback($patternDollars, function ($m) use (&$skipped) {
            $skipped[] = trim($m[0]);

            return '';
        }, $sql);

        // 2) Remove more compact CREATE FUNCTION/PROCEDURE; ... ; blocks (best-effort)
        $patternSimple = '/(CREATE\s+(OR\s+REPLACE\s+)?(FUNCTION|PROCEDURE)\b.*?;)/is';
        $sql = preg_replace_callback($patternSimple, function ($m) use (&$skipped) {
            $skipped[] = trim($m[0]);

            return '';
        }, $sql);

        // 3) Remove CREATE TRIGGER blocks (which may reference functions)
        $patternTrigger = '/(CREATE\s+(OR\s+REPLACE\s+)?TRIGGER\b.*?;)/is';
        $sql = preg_replace_callback($patternTrigger, function ($m) use (&$skipped) {
            $skipped[] = trim($m[0]);

            return '';
        }, $sql);

        // Trim leftover whitespace and return
        return [trim($sql), implode("\n\n-- SKIPPED DEFINITION --\n\n", $skipped)];
    }

    /**
     * Extract LLM content from common response shapes or return raw body.
     */
    private function extractLlmContent(string $body): string
    {
        $data = json_decode($body, true);
        if (is_array($data)) {
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
            if (isset($data['choices'][0]['text'])) {
                return $data['choices'][0]['text'];
            }
            if (isset($data['data'][0]['text'])) {
                return $data['data'][0]['text'];
            }
        }

        return $body;
    }

    /**
     * Remove markdown fences and surrounding noise to isolate JSON text.
     */
    private function cleanLlmJson(string $raw): string
    {
        // Remove common code fences
        $clean = preg_replace('/^```[a-zA-Z0-9]*\n|\n```$/', '', trim($raw));

        // Remove any leading text before first { and trailing after last }
        $first = strpos($clean, '{');
        $last = strrpos($clean, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $clean = substr($clean, $first, $last - $first + 1);
        }

        // Remove problematic non-printable control characters that break json_decode
        // Keep common whitespace (LF, CR, TAB) but remove other control codes.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);

        return trim($clean);
    }

    /**
     * Validate decoded JSON and ensure it follows minimal required schema/rules.
     * Returns decoded array on success, throws on failure.
     */
    private function validateAndDecodeJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Attempt simple repairs: balance braces/brackets and close unterminated quotes
            $repaired = $json;

            // Remove trailing commas before closing
            $repaired = preg_replace('/,\s*([}\]])/', '$1', $repaired);

            // Balance braces/brackets by appending missing closing characters
            $openBrace = substr_count($repaired, '{');
            $closeBrace = substr_count($repaired, '}');
            if ($openBrace > $closeBrace) {
                $repaired .= str_repeat('}', $openBrace - $closeBrace);
            }
            $openBracket = substr_count($repaired, '[');
            $closeBracket = substr_count($repaired, ']');
            if ($openBracket > $closeBracket) {
                $repaired .= str_repeat(']', $openBracket - $closeBracket);
            }

            // Close an unclosed double-quote if present (count unescaped quotes)
            $totalQuotes = substr_count($repaired, '"');
            $escapedQuotes = substr_count($repaired, '\\"');
            $unescaped = $totalQuotes - $escapedQuotes;
            if ($unescaped % 2 === 1) {
                // append a double-quote to try closing an unterminated string
                $repaired .= '"';
            }

            $decoded = json_decode($repaired, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // As a last resort, attempt to salvage a valid JSON prefix by trimming the end
                $salvaged = $this->salvageJsonPrefix($repaired);
                if ($salvaged !== null) {
                    $decoded = json_decode($salvaged, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // salvage successful; don't attempt to write file here (no runId scope)
                    }
                }

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('LLM JSON parse failed: '.json_last_error_msg());
                }
            }
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException('LLM JSON must decode into an object');
        }

        // Ensure required array keys exist (with defaults if missing)
        $decoded['tables'] = $decoded['tables'] ?? [];
        $decoded['stored_procedures'] = $decoded['stored_procedures'] ?? [];
        $decoded['functions'] = $decoded['functions'] ?? [];
        $decoded['triggers'] = $decoded['triggers'] ?? [];
        $decoded['dcl'] = $decoded['dcl'] ?? [];

        // Validate that required keys are actually arrays
        foreach (['tables', 'stored_procedures', 'functions', 'triggers'] as $key) {
            if (! is_array($decoded[$key])) {
                throw new \RuntimeException("Key '{$key}' must be an array, got: ".gettype($decoded[$key]));
            }
        }

        // At least tables must not be empty or must have structure
        if (empty($decoded['tables'])) {
            throw new \RuntimeException('JSON must include at least one table definition');
        }

        $allowedTypes = ['id', 'string', 'integer', 'text', 'boolean', 'date', 'datetime', 'decimal', 'INTEGER', 'VARCHAR', 'VARCHAR(50)', 'VARCHAR(100)', 'VARCHAR(150)', 'VARCHAR(255)', 'TEXT', 'DATETIME', 'DECIMAL', 'DECIMAL(10,2)', 'DECIMAL(12,2)', 'INT', 'BIGINT', 'SERIAL', 'BIGSERIAL', 'BOOLEAN', 'DATE', 'TIMESTAMP', 'CURRENT_TIMESTAMP'];

        foreach ($decoded['tables'] as $table) {
            if (! isset($table['name']) || ! isset($table['columns']) || ! is_array($table['columns'])) {
                throw new \RuntimeException('Each table must have a name and columns array');
            }
            foreach ($table['columns'] as $col) {
                if (! isset($col['type']) || ! isset($col['name'])) {
                    throw new \RuntimeException('Each column must have type and name');
                }
                // More flexible type checking - allow both shorthand and full SQL types
                $colType = (string) $col['type'];
                if (! in_array($colType, $allowedTypes, true) && ! preg_match('/^[A-Z]+(\([^)]+\))?$/', $colType)) {
                    // Allow any uppercase type with optional parentheses
                }
                
                if ($col['type'] === 'id' && $col['name'] !== 'id') {
                    throw new \RuntimeException('Type "id" may only be used for primary key named "id"');
                }
                if ($col['name'] !== 'id' && preg_match('/_id$/', (string) $col['name']) === 1 && $col['type'] !== 'integer') {
                    throw new \RuntimeException('Foreign key columns ending with _id must use type "integer"');
                }
            }
        }

        foreach ($decoded['triggers'] as $trigger) {
            $definition = strtolower((string) ($trigger['definition'] ?? ''));
            if ($definition !== '' && (str_contains($definition, 'execute procedure') || preg_match('/\bcall\b\s+[a-z_][a-z0-9_]*\s*\(/', $definition) === 1)) {
                throw new \RuntimeException('Invalid trigger definition: PostgreSQL triggers must invoke functions, not procedures');
            }
        }

        return $decoded;
    }

    /**
     * Convert the validated schema into SQL DDL string.
     */
    /**
     * Convert the validated schema into categorized SQL parts.
     * Returns an array: ['ddl' => string, 'dml' => string, 'trigger' => string]
     */
    private function llmJsonToSqlCategorized(array $schema): array
    {
        $ddl = [];
        $dml = [];
        $functions = [];
        $storedProcedures = [];
        $triggers = [];

        // Build a map of table names for foreign key resolution
        $tableMap = [];
        if (!empty($schema['tables']) && is_array($schema['tables'])) {
            foreach ($schema['tables'] as $table) {
                if (isset($table['name'])) {
                    $tableMap[strtolower($table['name'])] = $table['name'];
                }
            }
        }

        foreach ($schema['tables'] as $table) {
            // Sanitize table name: replace invalid chars (including '-') with underscore
            $rawTableName = isset($table['name']) ? (string) $table['name'] : 'table_'.uniqid();
            $safeTableName = preg_replace('/[^A-Za-z0-9_]/', '_', $rawTableName);

            $cols = [];
            $primaryKeys = [];
            $foreignKeys = [];
            
            if (!empty($table['columns']) && is_array($table['columns'])) {
                foreach ($table['columns'] as $col) {
                    $colDef = $this->buildColumnDefinition($col, $tableMap);
                    if ($colDef) {
                        $cols[] = $colDef;
                        
                        // Track primary keys
                        if (!empty($col['primary_key'])) {
                            $primaryKeys[] = preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) $col['name']));
                        }
                        
                        // Track foreign keys
                        if (!empty($col['foreign_key']) && is_array($col['foreign_key'])) {
                            $fkTable = preg_replace('/[^A-Za-z0-9_]/', '_', $col['foreign_key']['table'] ?? '');
                            $fkColumn = preg_replace('/[^A-Za-z0-9_]/', '_', $col['foreign_key']['column'] ?? 'id');
                            $colName = preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) $col['name']));
                            if ($fkTable) {
                                $foreignKeys[] = sprintf(
                                    'FOREIGN KEY ("%s") REFERENCES "%s"("%s")',
                                    $colName,
                                    $fkTable,
                                    $fkColumn
                                );
                            }
                        }
                    }
                }
            }
            
            // Add primary key constraint if not already part of column def
            if (!empty($primaryKeys) && count($primaryKeys) > 1) {
                $cols[] = sprintf('PRIMARY KEY ("%s")', implode('", "', $primaryKeys));
            }
            
            // Add foreign key constraints
            $cols = array_merge($cols, $foreignKeys);

            $ddl[] = sprintf('CREATE TABLE IF NOT EXISTS "%s" (%s);', $safeTableName, implode(', ', $cols));

            // DML: dummy data
            if (! empty($table['dummy_data']) && is_array($table['dummy_data'])) {
                $declaredCols = [];
                if (!empty($table['columns']) && is_array($table['columns'])) {
                    foreach ($table['columns'] as $c) {
                        if (isset($c['name'])) {
                            $declaredCols[] = preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) $c['name']));
                        }
                    }
                }

                foreach ($table['dummy_data'] as $row) {
                    $keyMap = [];
                    foreach (array_keys($row) as $k) {
                        $keyMap[strtolower(trim($k))] = $k;
                    }

                    $insertCols = [];
                    $insertVals = [];

                    foreach ($declaredCols as $colName) {
                        $lookup = strtolower($colName);
                        if (array_key_exists($lookup, $keyMap)) {
                            $origKey = $keyMap[$lookup];
                            $v = $row[$origKey];
                            if (is_null($v)) {
                                $insertVals[] = 'NULL';
                            } elseif (is_bool($v)) {
                                $insertVals[] = $v ? 'TRUE' : 'FALSE';
                            } else {
                                $insertVals[] = "'".str_replace("'", "''", (string) $v)."'";
                            }
                            $insertCols[] = $colName;
                        }
                    }

                    if (! empty($insertCols)) {
                        $dml[] = sprintf('INSERT INTO "%s" ("%s") VALUES (%s);', $safeTableName, implode('", "', $insertCols), implode(', ', $insertVals));
                    }
                }
            }
        }

        // functions — handle both 'definition' and new format with name/parameters
        if (! empty($schema['functions']) && is_array($schema['functions'])) {
            foreach ($schema['functions'] as $f) {
                if (!empty($f['definition'])) {
                    // Legacy format with definition
                    $functions[] = trim($f['definition']);
                } elseif (!empty($f['name'])) {
                    // New format with name and parameters
                    $funcDef = $this->buildFunctionDefinition($f);
                    if ($funcDef) {
                        $functions[] = $funcDef;
                    }
                }
            }
        }

        // stored procedures
        if (! empty($schema['stored_procedures']) && is_array($schema['stored_procedures'])) {
            foreach ($schema['stored_procedures'] as $p) {
                if (!empty($p['definition'])) {
                    $storedProcedures[] = trim($p['definition']);
                } elseif (!empty($p['name'])) {
                    $procDef = $this->buildProcedureDefinition($p);
                    if ($procDef) {
                        $storedProcedures[] = $procDef;
                    }
                }
            }
        }

        // triggers — handle both 'definition' and new format with event/table/statement
        if (! empty($schema['triggers']) && is_array($schema['triggers'])) {
            foreach ($schema['triggers'] as $t) {
                if (!empty($t['definition'])) {
                    // Legacy format with definition
                    $def = $t['definition'];
                    $def = str_ireplace('EXECUTE PROCEDURE', 'EXECUTE FUNCTION', $def);
                    $def = str_ireplace('PROCEDURE', 'FUNCTION', $def);
                    $triggers[] = trim($def);
                } elseif (!empty($t['name']) && !empty($t['event']) && !empty($t['table'])) {
                    // New format with name, event, table, statement
                    $triggerDef = $this->buildTriggerDefinition($t);
                    if ($triggerDef) {
                        $triggers[] = $triggerDef;
                    }
                }
            }
        }

        // dcl — grants/revokes etc.
        $dcl = [];
        if (! empty($schema['dcl']) && is_array($schema['dcl'])) {
            foreach ($schema['dcl'] as $rule) {
                if (is_string($rule) && trim($rule) !== '') {
                    $dcl[] = trim($rule);
                }
            }
        }

        return [
            'ddl' => implode("\n\n", $ddl),
            'dcl' => implode("\n\n", $dcl),
            'dml' => implode("\n\n", $dml),
            'functions' => implode("\n\n", $functions),
            'stored_procedures' => implode("\n\n", $storedProcedures),
            'triggers' => implode("\n\n", $triggers),
            'trigger' => implode("\n\n", array_filter([
                implode("\n\n", $functions),
                implode("\n\n", $storedProcedures),
                implode("\n\n", $triggers),
            ])),
        ];
    }

    /**
     * Build a column definition from the column schema.
     */
    private function buildColumnDefinition(array $col, array $tableMap = []): ?string
    {
        $name = isset($col['name']) ? trim((string) $col['name']) : null;
        if (!$name) {
            return null;
        }

        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        $type = $col['type'] ?? 'TEXT';

        // Handle NULL/NOT NULL
        $nullable = empty($col['not_null']);
        
        // Handle AUTO_INCREMENT (use SERIAL or BIGSERIAL)
        $autoIncrement = !empty($col['auto_increment']);
        if ($autoIncrement) {
            if (stripos($type, 'INTEGER') !== false || $type === 'INT') {
                $type = 'SERIAL';
            } elseif (stripos($type, 'BIGINT') !== false) {
                $type = 'BIGSERIAL';
            }
        }

        $def = sprintf('"%s" %s', $name, $type);

        // Add PRIMARY KEY constraint if applicable
        if (!empty($col['primary_key']) && !$autoIncrement) {
            $def .= ' PRIMARY KEY';
        }

        // Add UNIQUE constraint
        if (!empty($col['unique'])) {
            $def .= ' UNIQUE';
        }

        // Add DEFAULT constraint
        if (isset($col['default'])) {
            $default = $col['default'];
            if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
                $def .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif (is_string($default)) {
                // Escape single quotes
                $def .= " DEFAULT '".str_replace("'", "''", $default)."'";
            } else {
                $def .= " DEFAULT ".(string) $default;
            }
        }

        // Add NOT NULL if specified
        if (!$nullable) {
            $def .= ' NOT NULL';
        }

        return $def;
    }

    /**
     * Build a function definition from the function schema.
     */
    private function buildFunctionDefinition(array $func): ?string
    {
        $name = $func['name'] ?? null;
        if (!$name) {
            return null;
        }

        // If there's already a body/statement, use it
        if (!empty($func['body'])) {
            return trim($func['body']);
        }

        // Build basic function signature
        $params = [];
        if (!empty($func['parameters']) && is_array($func['parameters'])) {
            foreach ($func['parameters'] as $param) {
                $paramName = $param['name'] ?? 'p_param';
                $paramType = $param['type'] ?? 'INTEGER';
                $params[] = "{$paramName} {$paramType}";
            }
        }

        $paramStr = !empty($params) ? implode(', ', $params) : '';
        $returns = $func['returns'] ?? 'INTEGER';
        $language = $func['language'] ?? 'plpgsql';
        $body = $func['statement'] ?? 'BEGIN RETURN 0; END;';

        // Build CREATE FUNCTION statement
        $def = "CREATE OR REPLACE FUNCTION \"{$name}\"({$paramStr}) RETURNS {$returns} AS \$\$\n";
        $def .= "BEGIN\n";
        $def .= "  {$body}\n";
        $def .= "END;\n";
        $def .= "\$\$ LANGUAGE {$language};";

        return $def;
    }

    /**
     * Build a procedure definition from the procedure schema.
     */
    private function buildProcedureDefinition(array $proc): ?string
    {
        $name = $proc['name'] ?? null;
        if (!$name) {
            return null;
        }

        // If there's already a body, use it
        if (!empty($proc['body'])) {
            return trim($proc['body']);
        }

        // Build basic procedure signature
        $params = [];
        if (!empty($proc['parameters']) && is_array($proc['parameters'])) {
            foreach ($proc['parameters'] as $param) {
                $paramName = $param['name'] ?? 'p_param';
                $paramType = $param['type'] ?? 'INTEGER';
                $params[] = "{$paramName} {$paramType}";
            }
        }

        $paramStr = !empty($params) ? implode(', ', $params) : '';
        $language = $proc['language'] ?? 'plpgsql';
        $body = $proc['statement'] ?? 'BEGIN RETURN 0; END;';

        // Build CREATE PROCEDURE statement
        $def = "CREATE OR REPLACE PROCEDURE \"{$name}\"({$paramStr}) AS \$\$\n";
        $def .= "BEGIN\n";
        $def .= "  {$body}\n";
        $def .= "END;\n";
        $def .= "\$\$ LANGUAGE {$language};";

        return $def;
    }

    /**
     * Build a trigger definition from the trigger schema.
     */
    private function buildTriggerDefinition(array $trigger): ?string
    {
        $name = $trigger['name'] ?? null;
        $event = strtoupper($trigger['event'] ?? 'AFTER INSERT');
        $table = $trigger['table'] ?? null;
        $statement = $trigger['statement'] ?? null;

        if (!$name || !$table || !$statement) {
            return null;
        }

        // Sanitize names
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        $table = preg_replace('/[^A-Za-z0-9_]/', '_', $table);

        // Build CREATE TRIGGER statement
        $def = "CREATE OR REPLACE TRIGGER \"{$name}\"\n";
        $def .= "{$event} ON \"{$table}\"\n";
        $def .= "FOR EACH ROW\n";
        $def .= "BEGIN\n";
        $def .= "  {$statement}\n";
        $def .= "END;";

        return $def;
    }

    /**
     * Extract table overview from schema for display in SQL review panel.
     */
    private function extractTableOverview(array $schema): array
    {
        $overview = [];

        if (empty($schema['tables']) || !is_array($schema['tables'])) {
            return $overview;
        }

        foreach ($schema['tables'] as $table) {
            $tableName = isset($table['name']) ? (string) $table['name'] : 'unknown_table';
            
            $columns = [];
            if (!empty($table['columns']) && is_array($table['columns'])) {
                foreach ($table['columns'] as $col) {
                    $colName = isset($col['name']) ? (string) $col['name'] : 'unknown_column';
                    $columns[] = [
                        'name' => $colName,
                        'type' => $col['type'] ?? 'TEXT',
                        'primary_key' => !empty($col['primary_key']),
                        'not_null' => !empty($col['not_null']),
                        'unique' => !empty($col['unique']),
                        'auto_increment' => !empty($col['auto_increment']),
                        'foreign_key' => $col['foreign_key'] ?? null,
                        'default' => $col['default'] ?? null,
                    ];
                }
            }

            $dummyDataCount = 0;
            if (!empty($table['dummy_data']) && is_array($table['dummy_data'])) {
                $dummyDataCount = count($table['dummy_data']);
            }

            $overview[] = [
                'name' => $tableName,
                'column_count' => count($columns),
                'columns' => $columns,
                'dummy_data_count' => $dummyDataCount,
            ];
        }

        return $overview;
    }

    private function createGeneratedSchemaName(string $runId, ?int $userId = null): string
    {
        $suffix = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $runId));
        $owner = $userId !== null ? 'u'.max(0, $userId).'_' : '';

        return substr('gen_'.$owner.$suffix, 0, 63);
    }

    private function ensureMainSchemaStructures(string $mainSchema): void
    {
        $safeSchema = str_replace('"', '""', $mainSchema);
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $safeSchema));
        DB::statement(sprintf('CREATE TABLE IF NOT EXISTS "%s"."generation_history" (
            id BIGSERIAL PRIMARY KEY,
            run_id VARCHAR(120) NOT NULL UNIQUE,
            user_id BIGINT NULL,
            user_email VARCHAR(255) NULL,
            schema_name VARCHAR(63) NOT NULL,
            prompt TEXT NOT NULL,
            status VARCHAR(32) NOT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )', $safeSchema));
    }

    private function insertGenerationHistory(string $mainSchema, array $payload): ?int
    {
        try {
            return (int) DB::table($mainSchema.'.generation_history')->insertGetId([
                'run_id' => (string) ($payload['run_id'] ?? ''),
                'user_id' => $payload['user_id'] ?? null,
                'user_email' => $payload['user_email'] ?? null,
                'schema_name' => (string) ($payload['schema_name'] ?? ''),
                'prompt' => (string) ($payload['prompt'] ?? ''),
                'status' => (string) ($payload['status'] ?? 'running'),
                'error_message' => $payload['error_message'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');
        } catch (\Throwable $e) {
            Log::warning('Failed to insert generation history: '.$e->getMessage());

            return null;
        }
    }

    private function updateGenerationHistory(string $mainSchema, ?int $historyId, array $payload): void
    {
        if ($historyId === null) {
            return;
        }

        try {
            DB::table($mainSchema.'.generation_history')
                ->where('id', $historyId)
                ->update([
                    'status' => (string) ($payload['status'] ?? 'failed'),
                    'error_message' => $payload['error_message'] ?? null,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to update generation history: '.$e->getMessage());
        }
    }

    /**
     * Try to find the largest prefix of $json that is valid JSON. Returns the prefix or null.
     */
    private function salvageJsonPrefix(string $json): ?string
    {
        $s = trim($json);
        $first = strpos($s, '{');
        if ($first === false) {
            return null;
        }

        $s = substr($s, $first);
        $len = strlen($s);
        $low = 0;
        $high = $len;
        $best = null;

        // Binary search for the largest prefix that decodes as JSON
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $candidate = substr($s, 0, $mid);
            if ($candidate === '') {
                $low = $mid + 1;

                continue;
            }
            json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $best = $candidate;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return $best;
    }

    /**
     * Extract a top-level JSON array value for a given key using bracket matching.
     * Returns the array text including the surrounding [ ... ] or null if not found.
     */
    private function extractJsonArraySection(string $json, string $key): ?string
    {
        $needle = '"'.$key.'"';
        $keyPos = stripos($json, $needle);
        if ($keyPos === false) {
            return null;
        }

        $colonPos = strpos($json, ':', $keyPos + strlen($needle));
        if ($colonPos === false) {
            return null;
        }

        $startPos = strpos($json, '[', $colonPos);
        if ($startPos === false) {
            return null;
        }

        $length = strlen($json);
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($i = $startPos; $i < $length; $i++) {
            $char = $json[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;

                continue;
            }

            if ($char === '[') {
                $depth++;

                continue;
            }

            if ($char === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($json, $startPos, $i - $startPos + 1);
                }
            }
        }

        return null;
    }
}
