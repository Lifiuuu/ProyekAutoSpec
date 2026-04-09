<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GenerationService
{
    /**
     * Generate SQL DDL from a prompt by calling an LLM, save it, and execute it.
     *
     * @param string $prompt
    * @return array{runId:string, sql:string} The run id and SQL generated
     * @throws \Exception
     */
    public function generate(string $prompt): array
    {
        $runId = uniqid('api_');
        $credentials = [
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'postgres'),
        ];

                $system = <<<SYS
        Kamu adalah Database Schema Generator yang ketat. UBAH deskripsi user menjadi JSON MURNI dan TEPAT.
        PERINTAH KETAT (IKUTI HURUF KECIL):
        1) Output HARUS valid JSON saja — TIDAK BOLEH ada penjelasan, komentar, atau teks apapun di luar JSON.
        2) Struktur JSON wajib minimal berisi kunci: "tables" (array). Contoh minimal:
        {
            "tables": [
                {
                    "name": "{$runId}_nama_tabel",
                    "columns": [{"type":"id","name":"id"}, {"type":"string","name":"nama"}],
                    "dummy_data": [{"id":1,"nama":"Contoh"}, {"id":2,"nama":"Contoh2"}, {"id":3,"nama":"Contoh3"}]
                }
            ]
        }
        3) JANGAN keluarkan SQL mentah di luar JSON. KAMU BOLEH dan DIHARAPKAN membuat Trigger dan Function jika logika database membutuhkannya (misal: update stok). Masukkan definisi SQL-nya ke dalam array `triggers` dan `functions` sebagai objek dengan kunci `definition`.
        4) Tipe kolom hanya boleh salah satu dari: "id", "string", "integer", "text", "boolean", "date", "datetime", "decimal". Jangan gunakan VARCHAR/INT/SMALLINT atau tipe SQL vendor lain.
        5) Jika menggunakan "id" tipe maka nama kolom harus "id" dan itu adalah primary key. Foreign key harus bertipe "integer" dan nama mengikuti konvensi `{table}_{id}`.
        6) SEMUA nama tabel WAJIB diawali dengan prefix "{$runId}_" (contoh: "{$runId}_buku"). Semua nama kolom harus hanya berisi huruf, angka, dan garis bawah (no spaces, no leading/trailing spaces).
        7) Sertakan minimal 3 baris di "dummy_data" untuk setiap tabel. Nilai harus cocok dengan tipe kolom.
        8) Jangan sertakan karakter kontrol atau baris terpotong — pastikan string JSON selesai dan valid. Gunakan hanya tanda kutip ganda untuk string.
        9) Jika ada ketidakpastian, kembalikan struktur JSON dengan kunci "error" berisi pesan singkat, jangan mengeluarkan teks non-JSON.

        WAJIB SERTAKAN:
        - Minimal 1 aturan DCL dasar (misal: "GRANT SELECT ON ALL TABLES IN SCHEMA public TO anon;"). Masukkan rekomendasi DCL ini ke dalam array JSON dengan kunci `dcl` (array of strings).
        - Minimal 1 contoh Trigger lengkap untuk kebutuhan umum (misal: mencatat waktu update atau log aktivitas). Ikuti aturan PostgreSQL: buat dulu `functions` (PL/pgSQL) lalu `triggers` yang memanggil function. Masukkan definisi function ke `functions` (array of objects with key `definition`) dan definisi trigger ke `triggers` (array of objects with key `definition`).

        Pastikan semua jawaban tetap DI DALAM JSON, mis.:
        {
          "tables": [...],
          "functions": [{"definition":"CREATE FUNCTION ... $$ ... $$ LANGUAGE plpgsql;"}],
          "triggers": [{"definition":"CREATE TRIGGER ..."}],
          "dcl": ["GRANT ...;", "REVOKE ...;"]
        }
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
            if (!empty($llmKey)) {
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
                            $decodedPrefix = json_decode('{"tables":' . $tablesSection . '}', true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPrefix)) {
                                $schema = $decodedPrefix;
                                $schemaWasSalvaged = true;

                                if (!empty($parsedFallback['dcl'])) {
                                    $fallbackDcl = array_values(array_filter(array_map('trim', preg_split('/\r?\n+/', $parsedFallback['dcl']))));
                                    if (!empty($fallbackDcl)) {
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
                                    'error' => 'LLM JSON parse failed: ' . $e2->getMessage(),
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
                                'error' => 'LLM JSON parse failed: ' . $e2->getMessage(),
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
            $tmpSqlPath = $sqlPath . '.tmp';
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
            if (!empty($skipped)) {
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

                // Persist the fixed SQL for debugging
                try {
                    Storage::disk('local')->put("generations/{$runId}.fixed.sql", $fixedSql);
                } catch (\Throwable $__) {
                    // ignore
                }

                // Use unprepared to allow multi-statement blocks (plpgsql $$...$$ etc.)
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
                        $ctx[] = sprintf('%4d: %s', $i, $lines[$i-1]);
                    }
                    $lineInfo = "\n--- SQL context (lines {$start}-{$end}) ---\n" . implode("\n", $ctx) . "\n";
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
                        $ctx[] = sprintf('%4d: %s', $i, $lines[$i-1]);
                    }
                    $lineInfo = "\n--- SQL context (approx position {$pos}, lines {$start}-{$end}) ---\n" . implode("\n", $ctx) . "\n";
                } else {
                    $snippet = substr($sql, 0, 800);
                    $lineInfo = "\n--- SQL snippet (first 800 chars) ---\n" . $snippet . "\n";
                }

                // Save the raw error and context for easier debugging
                try {
                    Storage::disk('local')->put("generations/{$runId}.error.txt", $origMsg . "\n" . $lineInfo);
                } catch (\Throwable $__) {
                    // ignore
                }

                // do not rethrow here; we'll return parsed SQL parts and include error info
            }

            // --- Tahap 2: Request OpenAPI JSON ---
            $groqKey = env('GROQ_API_KEY');
            $groqUrl = env('GROQ_API_URL', $llmUrl);

            if (!empty($groqUrl)) {
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
                    throw new \RuntimeException('OpenAPI response is not valid JSON: ' . json_last_error_msg());
                }

                $tmpOpenapi = $openapiPath . '.tmp';
                Storage::disk('local')->put($tmpOpenapi, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                Storage::disk('local')->move($tmpOpenapi, $openapiPath);
            } else {
                Log::warning('Groq URL not configured, skipping OpenAPI generation');
            }

            // --- Tahap 3: Request Postman Collection JSON ---
            if (!empty($groqUrl)) {
                $postmanSystem = <<<'SYS'
You are a generator that transforms a database schema into a Postman Collection v2.1.0 JSON file. Base URL: http://localhost:8000/rest/v1/. Output ONLY valid JSON conforming to Postman Collection v2.1.0. Include example headers for Supabase access: an 'apikey' header and an 'Authorization' header with 'Bearer <SUPABASE_KEY>'. Do not include explanatory text.
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
                    throw new \RuntimeException('Postman response is not valid JSON: ' . json_last_error_msg());
                }

                $tmpPostman = $postmanPath . '.tmp';
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

            if (!empty($schemaWasSalvaged) && !empty($parsedFallback)) {
                $retDdl = $retDdl !== '' ? $retDdl : ($parsedFallback['ddl'] ?? '');
                $retDcl = $retDcl !== '' ? $retDcl : ($parsedFallback['dcl'] ?? '');
                $retDml = $retDml !== '' ? $retDml : ($parsedFallback['dml'] ?? '');
                $retTrigger = $retTrigger !== '' ? $retTrigger : ($parsedFallback['trigger'] ?? '');
            }

            $result = [
                'runId' => $runId,
                'generatedSql' => [
                    'ddl' => $retDdl,
                    'dcl' => $retDcl,
                    'dml' => $retDml,
                    'trigger' => $retTrigger,
                ],
                'schemaOverview' => [
                    ...$schema,
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

            $result['downloads'] = $downloads;
            $result['files'] = $files;
            $result['schemaOverview']['downloads'] = $downloads;
            $result['schemaOverview']['files'] = $files;

            if (!empty($executionFailed)) {
                $result['error'] = 'SQL Execution skipped/failed: ' . ($executionErrorMsg ?? 'unknown') . '. See generations/' . $runId . '.error.txt';
            }

            return $result;
        } catch (\Throwable $e) {
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

            Log::error('GenerationService failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Try to extract SQL parts from raw LLM output when JSON parsing fails.
     * Returns array with keys: full, ddl, dml, dcl, trigger
     */
    private function parseSqlFromRaw(string $raw): array
    {
        $out = ['full' => $raw, 'ddl' => '', 'dml' => '', 'dcl' => '', 'trigger' => ''];

        // Remove markdown fences
        $clean = preg_replace('/^```[a-zA-Z0-9]*\n|\n```$/', '', trim($raw));

        // Regex patterns (more specific)
        $createTable = '/\bCREATE\s+TABLE\b[\s\S]*?;/i';
        $insert = '/\bINSERT\s+INTO\b[\s\S]*?;/i';
        $dcl = '/\b(?:GRANT|REVOKE)\b[\s\S]*?;/i';
        $funcDollar = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?(?:FUNCTION|PROCEDURE)\b[\s\S]*?\$\$[\s\S]*?\$\$\s*;?/i';
        $funcSimple = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?(?:FUNCTION|PROCEDURE)\b[\s\S]*?;/i';
        $trigger = '/\bCREATE\s+(?:OR\s+REPLACE\s+)?TRIGGER\b[\s\S]*?;/i';

        // 1) Extract DDL (CREATE TABLE) and DCL, TRIGGER/FUNCTION definitions from the original clean text
        preg_match_all($createTable, $clean, $mTables);
        preg_match_all($dcl, $clean, $mDcl);
        preg_match_all($trigger, $clean, $mTrigger);
        preg_match_all($funcDollar, $clean, $mFuncDollar);
        // funcSimple is a fallback; we'll capture but prefer dollar-quoted fuller bodies
        preg_match_all($funcSimple, $clean, $mFuncSimple);

        $out['ddl'] = !empty($mTables[0]) ? implode("\n\n", array_map('trim', $mTables[0])) : '';
        $out['dcl'] = !empty($mDcl[0]) ? implode("\n\n", array_map('trim', $mDcl[0])) : '';

        // Build trigger collection: include full function/procedure definitions and triggers
        $funcs = [];
        if (!empty($mFuncDollar[0])) $funcs = array_merge($funcs, $mFuncDollar[0]);
        if (!empty($mFuncSimple[0])) {
            // include only those not already captured by dollar-quoted pattern
            foreach ($mFuncSimple[0] as $f) {
                $add = true;
                foreach ($funcs as $ex) {
                    if (stripos($ex, substr($f, 0, 40)) !== false) { $add = false; break; }
                }
                if ($add) $funcs[] = $f;
            }
        }

        $trigs = [];
        if (!empty($mTrigger[0])) $trigs = array_merge($trigs, $mTrigger[0]);

        $out['trigger'] = implode("\n\n", array_map('trim', array_merge($funcs, $trigs)));

        // 2) Mask out function/trigger bodies so INSERT detection ignores INSERTs inside procedural code
        $masked = $clean;
        $allDefs = [];
        // Collect all procedural/trigger matches with offsets
        preg_match_all($funcDollar, $clean, $m1, PREG_OFFSET_CAPTURE);
        preg_match_all($funcSimple, $clean, $m2, PREG_OFFSET_CAPTURE);
        preg_match_all($trigger, $clean, $m3, PREG_OFFSET_CAPTURE);

        $matches = [];
        if (!empty($m1[0])) $matches = array_merge($matches, $m1[0]);
        if (!empty($m2[0])) $matches = array_merge($matches, $m2[0]);
        if (!empty($m3[0])) $matches = array_merge($matches, $m3[0]);

        // Sort by offset descending to safely replace substrings
        usort($matches, function ($a, $b) { return $b[1] <=> $a[1]; });
        foreach ($matches as $mc) {
            $text = $mc[0];
            $off = $mc[1];
            if ($off === false) continue;
            $len = strlen($text);
            // Replace the procedural block with spaces to keep offsets stable
            $masked = substr_replace($masked, str_repeat(' ', $len), $off, $len);
        }

        // 3) Extract INSERTs only from masked content (so those inside functions/triggers are ignored)
        preg_match_all($insert, $masked, $mInserts);
        $out['dml'] = !empty($mInserts[0]) ? implode("\n\n", array_map('trim', $mInserts[0])) : '';

        // Fallback: if DDL or DML still empty, attempt statement-splitting classification
        if (trim($out['ddl']) === '' || trim($out['dml']) === '') {
            // Split by semicolon first (preserve text without semicolons later)
            $parts = preg_split('/;\s*(\r?\n)?/m', $clean);

            foreach ($parts as $p) {
                $s = trim($p);
                if ($s === '') continue;

                $low = strtolower($s);
                if (preg_match('/^create\s+table\b/i', $s)) {
                    $candidate = $s;
                    // make sure it ends with ); style
                    if (!str_ends_with(trim($candidate), ';')) $candidate = trim($candidate) . ';';
                    if (trim($out['ddl']) === '') {
                        $out['ddl'] = $candidate;
                    } else {
                        $out['ddl'] .= "\n\n" . $candidate;
                    }
                    continue;
                }

                if (preg_match('/^insert\s+into\b/i', $s)) {
                    // ensure INSERTs inside functions are ignored by checking masked version
                    // if the same substring exists in masked, accept it
                    if (strpos($masked, $s) !== false) {
                        $candidate = $s;
                        if (!str_ends_with(trim($candidate), ';')) $candidate = trim($candidate) . ';';
                        if (trim($out['dml']) === '') {
                            $out['dml'] = $candidate;
                        } else {
                            $out['dml'] .= "\n\n" . $candidate;
                        }
                    }
                    continue;
                }

                if (preg_match('/^\s*(grant|revoke)\b/i', $s)) {
                    $candidate = $s;
                    if (!str_ends_with(trim($candidate), ';')) $candidate = trim($candidate) . ';';
                    if (trim($out['dcl']) === '') {
                        $out['dcl'] = $candidate;
                    } else {
                        $out['dcl'] .= "\n\n" . $candidate;
                    }
                    continue;
                }

                if (preg_match('/^create\s+(or\s+replace\s+)?(function|procedure|trigger)\b/i', $s)) {
                    $candidate = $s;
                    if (!str_ends_with(trim($candidate), ';')) $candidate = trim($candidate) . ';';
                    if (trim($out['trigger']) === '') {
                        $out['trigger'] = $candidate;
                    } else {
                        $out['trigger'] .= "\n\n" . $candidate;
                    }
                    continue;
                }
            }
        }

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
            function ($m) use ($isInQuotes) {
                $full = $m[0];
                $pos = strpos($GLOBALS['__gen_sql_tmp__'] ?? $m[0], $m[0]);
                // The callback doesn't know absolute position; we'll instead perform a safer replace below.
                return $m[1] . '_' . $m[2];
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
            $identQuoted = '"' . str_replace('"', '""', $ident) . '"';
            return 'ON TABLE ' . $schema . '.' . $identQuoted;
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
                    return 'ON ' . $parts[0] . '."' . $parts[1] . '"';
                }
                return 'ON "' . $ident2 . '"';
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
            return "";
        }, $sql);

        // 2) Remove more compact CREATE FUNCTION/PROCEDURE; ... ; blocks (best-effort)
        $patternSimple = '/(CREATE\s+(OR\s+REPLACE\s+)?(FUNCTION|PROCEDURE)\b.*?;)/is';
        $sql = preg_replace_callback($patternSimple, function ($m) use (&$skipped) {
            $skipped[] = trim($m[0]);
            return "";
        }, $sql);

        // 3) Remove CREATE TRIGGER blocks (which may reference functions)
        $patternTrigger = '/(CREATE\s+(OR\s+REPLACE\s+)?TRIGGER\b.*?;)/is';
        $sql = preg_replace_callback($patternTrigger, function ($m) use (&$skipped) {
            $skipped[] = trim($m[0]);
            return "";
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
                    throw new \RuntimeException('LLM JSON parse failed: ' . json_last_error_msg());
                }
            }
        }

        if (!is_array($decoded) || !isset($decoded['tables']) || !is_array($decoded['tables'])) {
            throw new \RuntimeException('LLM JSON does not contain required "tables" array');
        }

        $allowedTypes = ['id', 'string', 'integer', 'text', 'boolean', 'date', 'datetime', 'decimal'];

        foreach ($decoded['tables'] as $table) {
            if (!isset($table['name']) || !isset($table['columns']) || !is_array($table['columns'])) {
                throw new \RuntimeException('Each table must have a name and columns array');
            }
            foreach ($table['columns'] as $col) {
                if (!isset($col['type']) || !isset($col['name'])) {
                    throw new \RuntimeException('Each column must have type and name');
                }
                if (!in_array($col['type'], $allowedTypes, true)) {
                    throw new \RuntimeException('Invalid column type: ' . $col['type']);
                }
                if ($col['type'] === 'id' && $col['name'] !== 'id') {
                    throw new \RuntimeException('Type "id" may only be used for primary key named "id"');
                }
            }
        }

        // (Previously validated triggers for 'procedure' usage.)
        // Removed strict validation to avoid failing the whole generation
        // when the LLM uses legacy wording. Trigger definitions will be
        // auto-fixed during SQL generation.

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
        $triggers = [];

        foreach ($schema['tables'] as $table) {
            // Sanitize table name: replace invalid chars (including '-') with underscore
            $rawTableName = isset($table['name']) ? (string)$table['name'] : 'table_' . uniqid();
            $safeTableName = preg_replace('/[^A-Za-z0-9_]/', '_', $rawTableName);

            $cols = [];
            foreach ($table['columns'] as $col) {
                $name = isset($col['name']) ? trim((string)$col['name']) : 'col_' . uniqid();
                // sanitize column name
                $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
                $type = $col['type'];
                switch ($type) {
                    case 'id':
                        $cols[] = sprintf('"%s" BIGSERIAL PRIMARY KEY', $name);
                        break;
                    case 'integer':
                        $cols[] = sprintf('"%s" INTEGER', $name);
                        break;
                    case 'string':
                    case 'text':
                        $cols[] = sprintf('"%s" TEXT', $name);
                        break;
                    case 'boolean':
                        $cols[] = sprintf('"%s" BOOLEAN', $name);
                        break;
                    case 'date':
                        $cols[] = sprintf('"%s" DATE', $name);
                        break;
                    case 'datetime':
                        $cols[] = sprintf('"%s" TIMESTAMP', $name);
                        break;
                    case 'decimal':
                        $cols[] = sprintf('"%s" DECIMAL', $name);
                        break;
                    default:
                        throw new \RuntimeException('Unhandled type: ' . $type);
                }
            }

            $ddl[] = sprintf('CREATE TABLE IF NOT EXISTS "%s" (%s);', $safeTableName, implode(', ', $cols));

            // DML: dummy data
                if (!empty($table['dummy_data']) && is_array($table['dummy_data'])) {
                $declaredCols = [];
                foreach ($table['columns'] as $c) {
                    if (isset($c['name'])) {
                            $declaredCols[] = preg_replace('/[^A-Za-z0-9_]/', '_', trim((string)$c['name']));
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
                                $insertVals[] = "'" . str_replace("'", "''", (string)$v) . "'";
                            }
                            $insertCols[] = $colName;
                        }
                    }

                    if (!empty($insertCols)) {
                        $dml[] = sprintf('INSERT INTO "%s" ("%s") VALUES (%s);', $safeTableName, implode('", "', $insertCols), implode(', ', $insertVals));
                    }
                }
            }
        }

        // functions
        if (!empty($schema['functions']) && is_array($schema['functions'])) {
            foreach ($schema['functions'] as $f) {
                if (isset($f['definition'])) {
                    $triggers[] = $f['definition'];
                }
            }
        }

        // stored procedures
        if (!empty($schema['stored_procedures']) && is_array($schema['stored_procedures'])) {
            foreach ($schema['stored_procedures'] as $p) {
                if (isset($p['definition'])) {
                    $triggers[] = $p['definition'];
                }
            }
        }

        // triggers — normalize wording
        if (!empty($schema['triggers']) && is_array($schema['triggers'])) {
            foreach ($schema['triggers'] as $t) {
                if (isset($t['definition'])) {
                    $def = $t['definition'];
                    $def = str_ireplace('EXECUTE PROCEDURE', 'EXECUTE FUNCTION', $def);
                    $def = str_ireplace('PROCEDURE', 'FUNCTION', $def);
                    $triggers[] = $def;
                }
            }
        }

        // dcl — grants/revokes etc.
        $dcl = [];
        if (!empty($schema['dcl']) && is_array($schema['dcl'])) {
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
            'trigger' => implode("\n\n", $triggers),
        ];
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
        $needle = '"' . $key . '"';
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
