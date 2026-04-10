<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateArtifactsJob;
use App\Services\GenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratorController extends Controller
{
    /**
     * Accept a generation request with a prompt.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $prompt = $request->input('prompt');

        // If async flag is present, dispatch job as before
        if ($request->boolean('async')) {
            try {
                GenerateArtifactsJob::dispatch($prompt);

                return response()->json(['message' => 'Task queued']);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch GenerateArtifactsJob: '.$e->getMessage());

                return response()->json(['message' => 'Failed to queue task'], 500);
            }
        }

        // Synchronous generation for Dashboard (returns runId and links)
        $service = new GenerationService;
        try {
            $authUser = $request->user();
            $result = $service->generate($prompt, $authUser?->id, $authUser?->email);
        } catch (\Throwable $e) {
            Log::error('Generation failed: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Generation failed: '.$e->getMessage());
        }

        // If JSON requested, return the full structured payload so React can set state
        if ($request->wantsJson()) {
            // Expect generate() to return ['runId','generatedSql'=>['ddl','dml','trigger'],'schemaOverview']
            return response()->json(array_merge(['success' => true], $result));
        }

        // Non-AJAX: keep original behavior but surface runId and links
        $runId = is_array($result) && isset($result['runId']) ? $result['runId'] : null;
        $links = [];
        if ($runId) {
            $links = [
                'sql' => route('generations.download', ['runId' => $runId, 'type' => 'sql']),
                'openapi' => route('generations.download', ['runId' => $runId, 'type' => 'openapi']),
                'postman' => route('generations.download', ['runId' => $runId, 'type' => 'postman']),
            ];
        }

        return redirect()->back()->with(['status' => 'Generation complete', 'runId' => $runId, 'links' => $links]);
    }

    /**
     * Return generation history for the authenticated user.
     */
    public function history(Request $request)
    {
        $mainSchema = env('DB_MAIN_SCHEMA', 'autospec_main');

        // Try Request->user() first (if middleware provides it), otherwise parse token manually
        $authUser = $request->user();
        $userId = $authUser?->id ?? null;
        $userEmail = $authUser?->email ?? null;

        if (! $userId && ! $userEmail) {
            // Try to parse a simple JWT-like token produced by AuthController
            $authHeader = $request->header('Authorization', '') ?: $request->bearerToken();
            if ($authHeader) {
                $token = trim(str_ireplace('bearer', '', $authHeader));
                $token = trim($token);
                if ($token !== '') {
                    $parts = explode('.', $token);
                    if (count($parts) >= 2) {
                        $payload = $parts[1];
                        // base64url decode
                        $pad = strlen($payload) % 4;
                        if ($pad > 0) {
                            $payload .= str_repeat('=', 4 - $pad);
                        }
                        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
                        if (is_array($decoded)) {
                            $userId = $userId ?? ($decoded['sub'] ?? null);
                            $userEmail = $userEmail ?? ($decoded['email'] ?? null);
                        }
                    }
                }
            }
        }

        if (! $userId && ! $userEmail) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $query = DB::table($mainSchema.'.generation_history');
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('user_email', $userEmail);
            }

            $rows = $query->orderBy('created_at', 'desc')->limit(200)->get();

            $items = [];
            foreach ($rows as $r) {
                $runId = $r->run_id ?? null;

                $files = [];
                if ($runId) {
                    $sqlPath = "generations/{$runId}.fixed.sql";
                    $rawPath = "generations/{$runId}.raw.txt";
                    $openapiPath = "generations/{$runId}.openapi.json";
                    $postmanPath = "generations/{$runId}.postman.json";

                    if (Storage::disk('local')->exists($sqlPath)) {
                        $files['database.sql'] = Storage::disk('local')->get($sqlPath);
                    } elseif (Storage::disk('local')->exists($rawPath)) {
                        $files['database.sql'] = Storage::disk('local')->get($rawPath);
                    } else {
                        $files['database.sql'] = '';
                    }

                    $files['openapi.json'] = Storage::disk('local')->exists($openapiPath) ? Storage::disk('local')->get($openapiPath) : '';
                    $files['postman_collection.json'] = Storage::disk('local')->exists($postmanPath) ? Storage::disk('local')->get($postmanPath) : '';
                }

                $items[] = [
                    'id' => $runId ?? ('gen_'.$r->id),
                    'name' => $r->prompt ? (mb_strimwidth($r->prompt, 0, 60, '...')) : 'Generated DB',
                    'status' => $r->status ?? 'unknown',
                    'timestamp' => isset($r->created_at) ? (string) $r->created_at : null,
                    'description' => $r->prompt ?? null,
                    'payload' => [
                        'runId' => $runId,
                        'files' => $files,
                        'error_message' => $r->error_message ?? null,
                    ],
                ];
            }

            return response()->json(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            Log::error('Failed fetching generation history: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Download generated artifact by runId and type.
     * Type: sql | openapi | postman
     */
    public function download(string $runId, string $type)
    {
        $map = [
            'sql' => "{$runId}.sql",
            'openapi' => "{$runId}.openapi.json",
            'postman' => "{$runId}.postman.json",
        ];

        if (! isset($map[$type])) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $path = 'generations/'.$map[$type];
        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Stream download
        return response()->download(storage_path('app/'.$path));
    }
}
