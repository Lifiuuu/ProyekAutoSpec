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
     * @return string The SQL generated
     * @throws \Exception
     */
    public function generate(string $prompt): string
    {
        $runId = uniqid('api_');

        $system = <<<SYS
Kamu adalah Database Schema Generator yang akurat. Ubah deskripsi user menjadi JSON murni.
ATURAN KETAT:
1. Output HARUS valid JSON.
2. Struktur JSON wajib seperti ini:
{
    "tables": [
        {
            "name": "nama_tabel",
            "columns": [
                {"type": "id", "name": "id"},
                {"type": "integer", "name": "barang_id"},
                {"type": "string", "name": "nama"}
            ],
            "dummy_data": [ {"nama": "Buku"} ]
        }
    ],
    "stored_procedures": [
        { "name": "nama_sp", "definition": "CREATE OR REPLACE PROCEDURE..." }
    ],
    "functions": [
        { "name": "nama_fungsi", "definition": "CREATE OR REPLACE FUNCTION..." }
    ],
    "triggers": [
        { "name": "nama_trigger", "definition": "CREATE TRIGGER..." }
    ]
}
3. PENTING UNTUK TYPE: Hanya gunakan: "id", "string", "integer", "text", "boolean", "date", "datetime", "decimal". Jangan gunakan SQL asli seperti VARCHAR atau INT!
4. Gunakan "id" HANYA untuk Primary Key bernama "id". Untuk Foreign Key (seperti user_id), WAJIB gunakan "integer".
5. TRIGGER di PostgreSQL HANYA BOLEH memanggil FUNCTION, bukan PROCEDURE!
6. [SANGAT KRUSIAL] SEMUA nama tabel WAJIB diawali dengan prefix "{$runId}_". Contoh: Jika tabel buku, kamu WAJIB menamainya "{$runId}_buku". Jika tabel anggota, menjadi "{$runId}_anggota".
5. TRIGGER di PostgreSQL HANYA BOLEH memanggil FUNCTION, bukan PROCEDURE!
6. [SANGAT KRUSIAL] SEMUA nama tabel WAJIB diawali dengan prefix "{$runId}_". Contoh: Jika tabel buku, kamu WAJIB menamainya "{$runId}_buku". Jika tabel anggota, menjadi "{$runId}_anggota".
7. [PENTING] Buatkan minimal 3 baris "dummy_data" yang realistis untuk SETIAP tabel. SEMUA kolom WAJIB diisi dengan nilai yang sesuai tipe datanya (JANGAN biarkan ada nilai null)!
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

        // Begin transaction that covers SQL execution + artifact generation
        DB::beginTransaction();

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

            // Clean the raw LLM output from markdown fences and noise
            $cleanJson = $this->cleanLlmJson($raw);

            // Validate and decode JSON according to strict schema
            $schema = $this->validateAndDecodeJson($cleanJson);

            // Convert JSON schema to SQL DDL
            $sql = $this->llmJsonToSql($schema);

            // Save SQL atomically and execute
            $tmpSqlPath = $sqlPath . '.tmp';
            Storage::disk('local')->put($tmpSqlPath, $sql);
            Storage::disk('local')->move($tmpSqlPath, $sqlPath);

            DB::unprepared($sql);

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
            DB::commit();

            return $sql;
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
            throw new \RuntimeException('LLM JSON parse failed: ' . json_last_error_msg());
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
    private function llmJsonToSql(array $schema): string
    {
        $lines = [];

        foreach ($schema['tables'] as $table) {
            $cols = [];
            foreach ($table['columns'] as $col) {
                $name = $col['name'];
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

            $lines[] = sprintf('CREATE TABLE IF NOT EXISTS "%s" (%s);', $table['name'], implode(', ', $cols));

           // Optionally insert dummy data
            if (!empty($table['dummy_data']) && is_array($table['dummy_data'])) {
                foreach ($table['dummy_data'] as $row) {
                    $columns = array_map('trim', array_keys($row));
                    $values = array_map(function ($v) {
                        if (is_null($v)) return 'NULL';
                        if (is_bool($v)) return $v ? 'TRUE' : 'FALSE';
                        return "'" . str_replace("'", "''", (string)$v) . "'";
                    }, array_values($row));
                    $lines[] = sprintf('INSERT INTO "%s" ("%s") VALUES (%s);', $table['name'], implode('", "', $columns), implode(', ', $values));
                }
            }
        }

        // Functions
        if (!empty($schema['functions']) && is_array($schema['functions'])) {
            foreach ($schema['functions'] as $f) {
                if (isset($f['definition'])) {
                    $lines[] = $f['definition'];
                }
            }
        }

        // Stored procedures
        if (!empty($schema['stored_procedures']) && is_array($schema['stored_procedures'])) {
            foreach ($schema['stored_procedures'] as $p) {
                if (isset($p['definition'])) {
                    $lines[] = $p['definition'];
                }
            }
        }

        // Triggers — auto-fix legacy "PROCEDURE" wording to use "FUNCTION"
        if (!empty($schema['triggers']) && is_array($schema['triggers'])) {
            foreach ($schema['triggers'] as $t) {
                if (isset($t['definition'])) {
                    $def = $t['definition'];
                    // Auto-fix AI using old PostgreSQL trigger syntax
                    $def = str_ireplace('EXECUTE PROCEDURE', 'EXECUTE FUNCTION', $def);
                    $def = str_ireplace('PROCEDURE', 'FUNCTION', $def);
                    $lines[] = $def;
                }
            }
        }

        return implode("\n\n", $lines);
    }
}
