<?php
// Posts the SQL in storage/app/database.sql to GROQ_API_URL and saves outputs.
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "Running Groq verification...\n";

$groqUrl = env('GROQ_API_URL', 'http://127.0.0.1:9000');
$groqKey = env('GROQ_API_KEY');

$sql = Storage::disk('local')->get('database.sql');
if (empty($sql)) {
    echo "No database.sql found. Run the GenerationService first.\n";
    exit(1);
}

// OpenAPI call
echo "Requesting OpenAPI from Groq...\n";
$openapiSystem = "You are a converter that transforms SQL DDL into a strict OpenAPI 3.0.0 specification in pure JSON. Output ONLY valid JSON that conforms to OpenAPI 3.0.0 — do not include any explanatory text or comments.";

$resp = Http::withToken($groqKey)->post($groqUrl, [
    'model' => 'mock',
    'messages' => [
        ['role' => 'system', 'content' => $openapiSystem],
        ['role' => 'user', 'content' => $sql],
    ],
]);

$body = (string) $resp->body();
$decoded = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "OpenAPI response is not valid JSON: " . json_last_error_msg() . "\n";
    echo "Body:\n$body\n";
    exit(1);
}
Storage::disk('local')->put('openapi.json', json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved openapi.json\n";

// Postman call
echo "Requesting Postman collection from Groq...\n";
$postmanSystem = "You are a generator that transforms SQL DDL into a Postman Collection v2.1.0 JSON file. Output ONLY valid JSON conforming to Postman Collection v2.1.0. Include example headers for Supabase access: an 'apikey' header and an 'Authorization' header with 'Bearer <SUPABASE_KEY>'. Do not include explanatory text.";

$resp2 = Http::withToken($groqKey)->post($groqUrl, [
    'model' => 'mock',
    'messages' => [
        ['role' => 'system', 'content' => $postmanSystem],
        ['role' => 'user', 'content' => $sql],
    ],
]);

$body2 = (string) $resp2->body();
$decoded2 = json_decode($body2, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Postman response is not valid JSON: " . json_last_error_msg() . "\n";
    echo "Body:\n$body2\n";
    exit(1);
}

Storage::disk('local')->put('postman_collection.json', json_encode($decoded2, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved postman_collection.json\n";

echo "Groq verification completed.\n";
