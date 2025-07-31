<?php

require_once __DIR__ . "/../vendor/autoload.php";

// Load environment variables
if (file_exists(__DIR__ . "/../.env")) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();
}

// Simple routing
$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];

// Basic routing logic
if ($uri === "/" || $uri === "/index.php") {
    $app = new App\App();
    echo $app->run();
} else {
    http_response_code(404);
    echo "Page not found";
} 