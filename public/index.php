<?php

require_once __DIR__ . "/../vendor/autoload.php";

// Load environment variables
if (file_exists(__DIR__ . "/../.env")) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();
}

// Get request details
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$headers = getallheaders() ?: [];
$body = file_get_contents('php://input') ?: '';

// Handle the request
$server = new FireUp\PhpBuild\Web\Server();
$response = $server->handleRequest($method, $path, $headers, $body);

// Output response
echo $response; 