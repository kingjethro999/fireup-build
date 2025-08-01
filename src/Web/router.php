<?php

// Simple router for the FireUp PHP Build web server
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Handle CORS preflight requests
if ($requestMethod === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, x-api-key');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Set CORS headers for all responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-api-key');

// Route requests
switch ($path) {
    case '/':
        // Serve the main interface
        echo '<!DOCTYPE html>
<html>
<head>
    <title>FireUp PHP Build</title>
    <meta http-equiv="refresh" content="0;url=/.chat/interface.html">
</head>
<body>
    <p>Redirecting to chat interface...</p>
</body>
</html>';
        break;

    case '/.chat/interface.html':
        // Serve the chat interface
        $server = new \FireUp\PhpBuild\Web\Server();
        echo $server->serveChatInterface();
        break;

    case '/.chat/api':
        // Handle chat API requests
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }

        $input = file_get_contents('php://input');
        $server = new \FireUp\PhpBuild\Web\Server();
        echo $server->handleChatRequest($input);
        break;

    case '/api/health':
        // Health check endpoint
        echo json_encode([
            'status' => 'ok',
            'message' => 'FireUp PHP Build server is running',
            'timestamp' => date('c'),
            'version' => '1.1.0'
        ]);
        break;

    default:
        // Check if it's a static file request
        $filePath = __DIR__ . '/../../' . $path;
        
        if (file_exists($filePath) && is_file($filePath)) {
            // Serve static file
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'html' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon'
            ];
            
            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
            }
            
            readfile($filePath);
        } else {
            // 404 Not Found
            http_response_code(404);
            echo json_encode([
                'error' => 'Not found',
                'path' => $path,
                'message' => 'The requested resource was not found on this server.'
            ]);
        }
        break;
} 