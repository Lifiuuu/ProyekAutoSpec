<?php
// Simple router for PHP built-in server to mock Groq responses.
// Run: php -S 127.0.0.1:9000 tools/groq_mock_router.php

// Read request body
$body = file_get_contents('php://input');
$data = json_decode($body, true) ?: [];

$messages = $data['messages'] ?? [];
$system = '';
foreach ($messages as $m) {
    if (isset($m['role']) && $m['role'] === 'system') {
        $system = $m['content'];
        break;
    }
}

header('Content-Type: application/json');

if (stripos($system, 'OpenAPI') !== false) {
    // return a minimal valid OpenAPI 3.0.0 JSON
    $openapi = [
        'openapi' => '3.0.0',
        'info' => ['title' => 'Mock API', 'version' => '1.0.0'],
        'paths' => new stdClass(),
    ];
    echo json_encode($openapi);
    exit(0);
}

if (stripos($system, 'Postman Collection') !== false) {
    // return a minimal Postman Collection v2.1.0 JSON
    $postman = [
        'info' => ['name' => 'Mock Collection', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'],
        'item' => [],
    ];
    echo json_encode($postman);
    exit(0);
}

// Default response: echo back received body as JSON
echo json_encode(['received' => $data]);
