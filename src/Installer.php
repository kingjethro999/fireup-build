<?php

namespace FireUp\PhpBuild;

use Symfony\Component\Filesystem\Filesystem;

class Installer
{
    public static function postInstall(): void
    {
        $filesystem = new Filesystem();
        
        // Create necessary directories
        $directories = [
            'public',
            'src',
            'config',
            'templates',
            'logs'
        ];
        
        foreach ($directories as $dir) {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir);
            }
        }
        
        // Make the CLI executable
        $cliPath = 'bin/php-build';
        if ($filesystem->exists($cliPath)) {
            chmod($cliPath, 0755);
        }
        
        echo "✅ FireUp PHP Build installed successfully!\n";
        echo "🚀 Run 'php bin/php-build' to get started\n";
    }
    
    public static function postCreateProject(): void
    {
        $filesystem = new Filesystem();
        
        // Create a basic project structure
        $projectStructure = [
            'public/index.php' => self::getIndexPhp(),
            'src/App.php' => self::getAppPhp(),
            'config/app.php' => self::getAppConfig(),
            '.env.example' => self::getEnvExample(),
            'README.md' => self::getReadme(),
            '.gitignore' => self::getGitignore()
        ];
        
        foreach ($projectStructure as $path => $content) {
            $filesystem->mkdir(dirname($path));
            $filesystem->dumpFile($path, $content);
        }
        
        // Make the CLI executable
        $cliPath = 'bin/php-build';
        if ($filesystem->exists($cliPath)) {
            chmod($cliPath, 0755);
        }
        
        echo "🎉 Project created successfully!\n";
        echo "📁 Project structure initialized\n";
        echo "🚀 Run 'php bin/php-build serve' to start development\n";
        echo "💬 Run 'php bin/php-build chat' for interactive development\n";
    }
    
    private static function getIndexPhp(): string
    {
        return '<?php

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
}';
    }
    
    private static function getAppPhp(): string
    {
        return '<?php

namespace App;

class App
{
    public function run(): string
    {
        return "🚀 Welcome to your FireUp PHP Build application!\n\n" .
               "This is a clean, well-structured PHP application.\n" .
               "Start building your features by editing this file.\n\n" .
               "💡 Try running: php bin/php-build chat";
    }
}';
    }
    
    private static function getAppConfig(): string
    {
        return '<?php

return [
    "name" => "FireUp PHP Build App",
    "version" => "1.0.0",
    "environment" => $_ENV["APP_ENV"] ?? "development",
    "debug" => $_ENV["APP_DEBUG"] ?? true,
    "url" => $_ENV["APP_URL"] ?? "http://localhost:8000",
];';
    }
    
    private static function getEnvExample(): string
    {
        return '# Application Configuration
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (if needed)
DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=

# Security
APP_KEY=your-secret-key-here';
    }
    
    private static function getReadme(): string
    {
        return '# FireUp PHP Build Application

A clean PHP application built with FireUp PHP Build.

## Features

- Clean MVC architecture
- PSR-4 autoloading
- Modern PHP 8.0+ features
- Interactive development tools

## Quick Start

1. Install dependencies:
   ```bash
   composer install
   ```

2. Start development server:
   ```bash
   php bin/php-build serve
   ```

3. Use interactive chat for development:
   ```bash
   php bin/php-build chat
   ```

## Available Commands

- `php bin/php-build serve` - Start development server
- `php bin/php-build chat` - Interactive development chat
- `php bin/php-build build` - Build and optimize application
- `php bin/php-build debug` - Debug and validate code
- `php bin/php-build create project <name>` - Create new project
- `php bin/php-build create controller <name>` - Create controller
- `php bin/php-build create model <name>` - Create model

## Project Structure

```
├── public/          # Web root
├── src/             # Application source code
├── config/          # Configuration files
├── templates/       # View templates
├── vendor/          # Composer dependencies
├── bin/             # CLI tools
└── composer.json    # Project configuration
```

## Development

This project was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License';
    }
    
    private static function getGitignore(): string
    {
        return '/vendor/
.env
.idea/
.vscode/
*.log
.DS_Store
Thumbs.db
/logs/*
!logs/.gitkeep';
    }
} 