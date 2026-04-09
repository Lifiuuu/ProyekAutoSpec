<?php
// tools/run_end_to_end.php
// Usage: php tools/run_end_to_end.php "Your prompt here"

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$prompt = isset($argv[1]) ? $argv[1] : 'Create a minimal users table with id serial primary key, name text, email text unique, created_at timestamp, updated_at timestamp';

/** @var \App\Services\GenerationService $service */
$service = new \App\Services\GenerationService();

echo "Running end-to-end generation with prompt:\n" . $prompt . "\n";

try {
    $sql = $service->generate($prompt);
    echo "\nSQL generated and executed:\n";
    echo $sql . "\n\n";

    $storageRoot = storage_path('app');
    echo "Artifacts (storage/app):\n";
    $files = [
        'database.sql',
        'openapi.json',
        'postman_collection.json',
    ];
    foreach ($files as $f) {
        $p = $storageRoot . DIRECTORY_SEPARATOR . $f;
        if (file_exists($p)) {
            echo " - $f -> $p (" . filesize($p) . " bytes)\n";
        } else {
            echo " - $f -> NOT FOUND\n";
        }
    }
    echo "\nEnd-to-end run complete.\n";
    exit(0);
} catch (Throwable $e) {
    echo "Generation failed: " . $e->getMessage() . "\n";
    echo $e->__toString() . "\n";
    exit(2);
}
