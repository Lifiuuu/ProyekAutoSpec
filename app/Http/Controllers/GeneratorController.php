<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GenerateArtifactsJob;
use App\Services\GenerationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $service = new GenerationService();
        try {
            $result = $service->generate($prompt);
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

        if (!isset($map[$type])) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $path = 'generations/'.$map[$type];
        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Stream download
        return response()->download(storage_path('app/'.$path));
    }
}
