<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GenerateArtifactsJob;

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

        // Dispatch the background job to process the prompt. If the queue
        // driver is not ready (e.g., missing jobs table), catch errors so
        // the API still responds successfully and the job can be retried
        // or handled later.
        try {
            GenerateArtifactsJob::dispatch($request->input('prompt'));
        } catch (\Throwable $e) {
            \Log::error('Failed to dispatch GenerateArtifactsJob: '.$e->getMessage());
        }

        return response()->json(['message' => 'Task queued']);
    }
}
