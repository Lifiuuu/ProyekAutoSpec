<?php
// Script to drop the verify_service table created during verification.
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Dropping table verify_service if it exists...\n";

try {
    DB::unprepared('DROP TABLE IF EXISTS verify_service');
    echo "Dropped (or did not exist).\n";
} catch (Throwable $e) {
    echo "Error dropping table: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
