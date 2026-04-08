<?php
// Lightweight script to verify DB execution and storage writing without calling external LLM.
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "Running safe verification...\n";

$sql = "CREATE TABLE IF NOT EXISTS verify_service (id serial PRIMARY KEY);";

try {
    Storage::disk('local')->put('database.sql', $sql);
    echo "Wrote storage/app/database.sql\n";

    DB::unprepared($sql);
    echo "Executed SQL successfully.\n";

    $res = DB::select("SELECT to_regclass('public.verify_service') as tbl");
    $tbl = $res[0]->tbl ?? null;
    echo "Table present: ".($tbl ?? 'none')."\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Verification finished.\n";
