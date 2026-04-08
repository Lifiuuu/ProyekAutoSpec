<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateArtifactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $prompt;

    /**
     * Create a new job instance.
     *
     * @param mixed $prompt
     */
    public function __construct($prompt)
    {
        $this->prompt = $prompt;
    }

    /**
     * Execute the job.
     *
     * The actual implementation will call a Service in a later task.
     */
    public function handle()
    {
        try {
            // Call the GenerationService to produce and apply SQL DDL
            $service = new \App\Services\GenerationService();
            $service->generate($this->prompt);
        } catch (\Throwable $e) {
            \Log::error('GenerateArtifactsJob failed: '.$e->getMessage());
            // Re-throw or handle according to queue failure strategy
            throw $e;
        }
    }
}
