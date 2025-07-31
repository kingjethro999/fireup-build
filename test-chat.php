<?php

require_once __DIR__ . '/vendor/autoload.php';

use FireUp\PhpBuild\Services\ChatService;

echo "🧪 Testing FireUp PHP Build Chat Service\n";
echo "=====================================\n\n";

$chatService = new ChatService();

// Test 1: Project creation
echo "Test 1: Creating a new project\n";
$response = $chatService->processMessage('Create a new PHP project called test-blog');
echo "Response: " . $response['content'] . "\n";
echo "Type: " . $response['type'] . "\n";
if (isset($response['files'])) {
    echo "Files created: " . count($response['files']) . "\n";
    foreach ($response['files'] as $file) {
        echo "  - " . $file['path'] . "\n";
    }
}
echo "\n";

// Test 2: Class generation
echo "Test 2: Creating a User class\n";
$response = $chatService->processMessage('Create a User class with properties name, email, password');
echo "Response: " . $response['content'] . "\n";
echo "Type: " . $response['type'] . "\n";
if (isset($response['files'])) {
    echo "Files created: " . count($response['files']) . "\n";
    foreach ($response['files'] as $file) {
        echo "  - " . $file['path'] . "\n";
    }
}
echo "\n";

// Test 3: Controller generation
echo "Test 3: Creating a UserController\n";
$response = $chatService->processMessage('Create a UserController with CRUD methods');
echo "Response: " . $response['content'] . "\n";
echo "Type: " . $response['type'] . "\n";
if (isset($response['files'])) {
    echo "Files created: " . count($response['files']) . "\n";
    foreach ($response['files'] as $file) {
        echo "  - " . $file['path'] . "\n";
    }
}
echo "\n";

echo "✅ Tests completed!\n"; 