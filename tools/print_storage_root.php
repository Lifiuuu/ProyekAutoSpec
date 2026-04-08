<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo config('filesystems.disks.local.root') . PHP_EOL;
echo storage_path('app') . PHP_EOL;
echo 'CWD: ' . getcwd() . PHP_EOL;
