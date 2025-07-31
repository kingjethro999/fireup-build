<?php

/**
 * FireUp PHP Build - Demo Script
 * 
 * This script demonstrates the core functionality of the PHP Build package.
 * Run this script to see how the interactive development tools work.
 */

require_once __DIR__ . '/vendor/autoload.php';

use FireUp\PhpBuild\Services\ChatService;
use FireUp\PhpBuild\Services\CodeGenerator;
use FireUp\PhpBuild\Services\FileManager;

echo "🚀 FireUp PHP Build - Demo\n";
echo "========================\n\n";

// Initialize services
$chatService = new ChatService();
$codeGenerator = new CodeGenerator();
$fileManager = new FileManager();

// Demo 1: Chat Service
echo "1. Testing Chat Service\n";
echo "----------------------\n";

$messages = [
    "Create a new PHP project called 'demo-app'",
    "Create a User model with properties name, email, password",
    "Create a UserController",
    "Debug my PHP code"
];

foreach ($messages as $message) {
    echo "User: {$message}\n";
    $response = $chatService->processMessage($message);
    echo "AI: " . $response['content'] . "\n\n";
}

// Demo 2: Code Generation
echo "2. Testing Code Generation\n";
echo "-------------------------\n";

$generationRequests = [
    "create a Product class with properties id, name, price",
    "create a function calculateTotal",
    "create a controller ProductController"
];

foreach ($generationRequests as $request) {
    echo "Request: {$request}\n";
    $result = $codeGenerator->generateFromRequest($request);
    echo "Result: " . $result['content'] . "\n\n";
}

// Demo 3: File Operations
echo "3. Testing File Operations\n";
echo "-------------------------\n";

$fileOperations = [
    "create file demo.php",
    "edit file demo.php",
    "create file config.php"
];

foreach ($fileOperations as $operation) {
    echo "Operation: {$operation}\n";
    $result = $fileManager->processFileOperation($operation);
    echo "Result: " . $result['message'] . "\n\n";
}

// Demo 4: Project Creation
echo "4. Testing Project Creation\n";
echo "---------------------------\n";

echo "Creating a sample project structure...\n";
$projectResult = $codeGenerator->createProjectStructure("create a new PHP project called 'sample-project'");

if (isset($projectResult['files']) && !empty($projectResult['files'])) {
    echo "✅ Project created successfully!\n";
    echo "📁 Created files:\n";
    foreach ($projectResult['files'] as $file) {
        echo "   • {$file['path']}\n";
    }
} else {
    echo "❌ Project creation failed: " . $projectResult['content'] . "\n";
}

echo "\n🎉 Demo completed!\n";
echo "\nTo use the interactive features:\n";
echo "• Run: php bin/php-build chat\n";
echo "• Run: php bin/php-build chat --web\n";
echo "• Run: php bin/php-build serve\n"; 