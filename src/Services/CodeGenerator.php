<?php

namespace FireUp\PhpBuild\Services;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

// Fallback for when Symfony components aren't available
if (!class_exists('Symfony\Component\Filesystem\Filesystem')) {
    class_alias('stdClass', 'Symfony\Component\Filesystem\Filesystem');
}
if (!class_exists('Symfony\Component\Finder\Finder')) {
    class_alias('stdClass', 'Symfony\Component\Finder\Finder');
}

class CodeGenerator
{
    private Filesystem $filesystem;
    private string $projectRoot;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->projectRoot = getcwd();
    }

    public function generateFromRequest(string $message): array
    {
        $message = strtolower($message);
        
        if (str_contains($message, 'class')) {
            return $this->generateClass($message);
        }
        
        if (str_contains($message, 'function') || str_contains($message, 'method')) {
            return $this->generateFunction($message);
        }
        
        if (str_contains($message, 'controller')) {
            return $this->generateController($message);
        }
        
        if (str_contains($message, 'model')) {
            return $this->generateModel($message);
        }
        
        return [
            'content' => 'I can generate classes, functions, controllers, and models. Please be more specific.',
            'files' => []
        ];
    }

    public function createProjectStructure(string $message): array
    {
        $projectName = $this->extractProjectName($message);
        $projectPath = $this->projectRoot . '/' . $projectName;
        
        if ($this->filesystem->exists($projectPath)) {
            return [
                'content' => "Project '{$projectName}' already exists.",
                'files' => []
            ];
        }

        $this->filesystem->mkdir($projectPath);
        
        $files = [
            'composer.json' => $this->generateComposerJson($projectName),
            'public/index.php' => $this->generateIndexPhp(),
            'src/App.php' => $this->generateAppClass(),
            'src/Controllers/HomeController.php' => $this->generateHomeController(),
            'src/Models/User.php' => $this->generateUserModel(),
            'config/database.php' => $this->generateDatabaseConfig(),
            'templates/layout.html' => $this->generateLayoutTemplate(),
            'templates/home.html' => $this->generateHomeTemplate(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateReadme($projectName),
            '.gitignore' => $this->generateGitignore()
        ];

        $createdFiles = [];
        foreach ($files as $filePath => $content) {
            $fullPath = $projectPath . '/' . $filePath;
            $this->filesystem->mkdir(dirname($fullPath));
            $this->filesystem->dumpFile($fullPath, $content);
            $createdFiles[] = ['path' => $filePath, 'content' => $content];
        }

        return [
            'content' => "Project '{$projectName}' created successfully with a complete MVC structure!",
            'files' => $createdFiles
        ];
    }

    private function generateClass(string $message): array
    {
        $className = $this->extractClassName($message);
        $properties = $this->extractProperties($message);
        
        $classContent = $this->generateClassContent($className, $properties);
        $filePath = "src/Models/{$className}.php";
        $fullPath = $this->projectRoot . '/' . $filePath;
        
        // Create directory if it doesn't exist
        $this->filesystem->mkdir(dirname($fullPath));
        $this->filesystem->dumpFile($fullPath, $classContent);
        
        return [
            'content' => "✅ Generated class '{$className}' with properties: " . implode(', ', $properties) . "\n📄 Created file: {$filePath}",
            'files' => [['path' => $filePath, 'content' => $classContent]]
        ];
    }

    private function generateFunction(string $message): array
    {
        $functionName = $this->extractFunctionName($message);
        $functionContent = $this->generateFunctionContent($functionName);
        $filePath = "src/Helpers/{$functionName}.php";
        $fullPath = $this->projectRoot . '/' . $filePath;
        
        // Create directory if it doesn't exist
        $this->filesystem->mkdir(dirname($fullPath));
        $this->filesystem->dumpFile($fullPath, $functionContent);
        
        return [
            'content' => "✅ Generated function '{$functionName}'\n📄 Created file: {$filePath}",
            'files' => [['path' => $filePath, 'content' => $functionContent]]
        ];
    }

    private function generateController(string $message): array
    {
        $controllerName = $this->extractControllerName($message);
        $controllerContent = $this->generateControllerContent($controllerName);
        $filePath = "src/Controllers/{$controllerName}.php";
        $fullPath = $this->projectRoot . '/' . $filePath;
        
        // Create directory if it doesn't exist
        $this->filesystem->mkdir(dirname($fullPath));
        $this->filesystem->dumpFile($fullPath, $controllerContent);
        
        return [
            'content' => "✅ Generated controller '{$controllerName}'\n📄 Created file: {$filePath}",
            'files' => [['path' => $filePath, 'content' => $controllerContent]]
        ];
    }

    private function generateModel(string $message): array
    {
        $modelName = $this->extractModelName($message);
        $modelContent = $this->generateModelContent($modelName);
        $filePath = "src/Models/{$modelName}.php";
        $fullPath = $this->projectRoot . '/' . $filePath;
        
        // Create directory if it doesn't exist
        $this->filesystem->mkdir(dirname($fullPath));
        $this->filesystem->dumpFile($fullPath, $modelContent);
        
        return [
            'content' => "✅ Generated model '{$modelName}'\n📄 Created file: {$filePath}",
            'files' => [['path' => $filePath, 'content' => $modelContent]]
        ];
    }

    private function extractProjectName(string $message): string
    {
        if (preg_match('/(?:create|new|start)\s+(?:a\s+)?(?:php\s+)?project\s+(?:called\s+)?([a-zA-Z0-9_-]+)/', $message, $matches)) {
            return $matches[1];
        }
        return 'my-php-project';
    }

    private function extractClassName(string $message): string
    {
        if (preg_match('/(?:create|generate|make)\s+(?:a\s+)?(?:class\s+)?([A-Z][a-zA-Z0-9]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'MyClass';
    }

    private function extractProperties(string $message): array
    {
        $properties = [];
        if (preg_match_all('/(?:with\s+)?(?:properties?\s+)?([a-zA-Z0-9_,\s]+)/', $message, $matches)) {
            $props = explode(',', $matches[1][0]);
            foreach ($props as $prop) {
                $prop = trim($prop);
                if (!empty($prop) && !in_array($prop, ['properties', 'with', 'and'])) {
                    $properties[] = $prop;
                }
            }
        }
        return empty($properties) ? ['id', 'name', 'email'] : $properties;
    }

    private function extractFunctionName(string $message): string
    {
        if (preg_match('/(?:create|generate|make)\s+(?:a\s+)?(?:function\s+)?([a-zA-Z0-9_]+)/', $message, $matches)) {
            return $matches[1];
        }
        return 'myFunction';
    }

    private function extractControllerName(string $message): string
    {
        if (preg_match('/(?:create|generate|make)\s+(?:a\s+)?(?:controller\s+)?([A-Z][a-zA-Z0-9]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'MyController';
    }

    private function extractModelName(string $message): string
    {
        if (preg_match('/(?:create|generate|make)\s+(?:a\s+)?(?:model\s+)?([A-Z][a-zA-Z0-9]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'MyModel';
    }

    private function generateClassContent(string $className, array $properties): string
    {
        $propertiesCode = '';
        $constructorParams = '';
        $constructorAssignments = '';
        
        foreach ($properties as $property) {
            $propertiesCode .= "    private \${$property};\n";
            $constructorParams .= "\${$property}, ";
            $constructorAssignments .= "        \$this->{$property} = \${$property};\n";
        }
        
        $constructorParams = rtrim($constructorParams, ', ');
        
        return "<?php

namespace App\Models;

class {$className}
{
{$propertiesCode}
    public function __construct({$constructorParams})
    {
{$constructorAssignments}    }
    
    // Getters
" . $this->generateGetters($properties) . "
    // Setters
" . $this->generateSetters($properties) . "
}";
    }

    private function generateGetters(array $properties): string
    {
        $getters = '';
        foreach ($properties as $property) {
            $getters .= "    public function get" . ucfirst($property) . "()\n    {\n        return \$this->{$property};\n    }\n\n";
        }
        return $getters;
    }

    private function generateSetters(array $properties): string
    {
        $setters = '';
        foreach ($properties as $property) {
            $setters .= "    public function set" . ucfirst($property) . "(\${$property})\n    {\n        \$this->{$property} = \${$property};\n        return \$this;\n    }\n\n";
        }
        return $setters;
    }

    private function generateFunctionContent(string $functionName): string
    {
        return "<?php

namespace App\Helpers;

function {$functionName}(\$param = null)
{
    // TODO: Implement function logic
    return \$param;
}";
    }

    private function generateControllerContent(string $controllerName): string
    {
        return "<?php

namespace App\Controllers;

class {$controllerName}
{
    public function index()
    {
        // TODO: Implement index method
        return 'Hello from {$controllerName}!';
    }
    
    public function show(\$id)
    {
        // TODO: Implement show method
        return 'Showing item with ID: ' . \$id;
    }
    
    public function create()
    {
        // TODO: Implement create method
        return 'Create new item form';
    }
    
    public function store()
    {
        // TODO: Implement store method
        return 'Item created successfully';
    }
}";
    }

    private function generateModelContent(string $modelName): string
    {
        return "<?php

namespace App\Models;

class {$modelName}
{
    private \$id;
    private \$name;
    private \$created_at;
    private \$updated_at;
    
    public function __construct(\$id = null, \$name = null)
    {
        \$this->id = \$id;
        \$this->name = \$name;
        \$this->created_at = date('Y-m-d H:i:s');
        \$this->updated_at = date('Y-m-d H:i:s');
    }
    
    public function getId()
    {
        return \$this->id;
    }
    
    public function getName()
    {
        return \$this->name;
    }
    
    public function setName(\$name)
    {
        \$this->name = \$name;
        \$this->updated_at = date('Y-m-d H:i:s');
        return \$this;
    }
    
    public function save()
    {
        // TODO: Implement database save logic
        return true;
    }
    
    public static function find(\$id)
    {
        // TODO: Implement database find logic
        return new self(\$id, 'Sample {$modelName}');
    }
}";
    }

    private function generateComposerJson(string $projectName): string
    {
        return '{
    "name": "' . strtolower($projectName) . '/app",
    "description": "A clean PHP application built with FireUp PHP Build",
    "type": "project",
    "require": {
        "php": ">=8.0"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "src/"
        }
    }
}';
    }

    private function generateIndexPhp(): string
    {
        return '<?php

require_once __DIR__ . "/../vendor/autoload.php";

// Simple routing
$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];

// Basic routing logic
if ($uri === "/" || $uri === "/index.php") {
    $controller = new App\Controllers\HomeController();
    echo $controller->index();
} else {
    http_response_code(404);
    echo "Page not found";
}';
    }

    private function generateAppClass(): string
    {
        return '<?php

namespace App;

class App
{
    public function run()
    {
        // TODO: Implement application bootstrap
        echo "FireUp PHP Build Application Running!";
    }
}';
    }

    private function generateHomeController(): string
    {
        return '<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return "Welcome to your FireUp PHP Build application!";
    }
}';
    }

    private function generateUserModel(): string
    {
        return '<?php

namespace App\Models;

class User
{
    private $id;
    private $name;
    private $email;
    private $created_at;
    
    public function __construct($id = null, $name = null, $email = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->created_at = date("Y-m-d H:i:s");
    }
    
    // Getters and setters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getCreatedAt() { return $this->created_at; }
    
    public function setName($name) { $this->name = $name; return $this; }
    public function setEmail($email) { $this->email = $email; return $this; }
}';
    }

    private function generateDatabaseConfig(): string
    {
        return '<?php

return [
    "host" => $_ENV["DB_HOST"] ?? "localhost",
    "database" => $_ENV["DB_NAME"] ?? "myapp",
    "username" => $_ENV["DB_USER"] ?? "root",
    "password" => $_ENV["DB_PASS"] ?? "",
    "charset" => "utf8mb4",
    "options" => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];';
    }

    private function generateLayoutTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? "FireUp PHP Build" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto">
            <h1 class="text-xl font-bold">FireUp PHP Build</h1>
        </div>
    </nav>
    
    <main class="container mx-auto p-4">
        <?= $content ?? "" ?>
    </main>
    
    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>Built with FireUp PHP Build</p>
        </div>
    </footer>
</body>
</html>';
    }

    private function generateHomeTemplate(): string
    {
        return '<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-4">Welcome to Your Application</h2>
    <p class="text-gray-600 mb-4">
        This is a clean, well-structured PHP application built with FireUp PHP Build.
    </p>
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <p class="text-blue-700">
            <strong>Next steps:</strong>
        </p>
        <ul class="list-disc list-inside mt-2 text-blue-600">
            <li>Add your business logic</li>
            <li>Connect to a database</li>
            <li>Add authentication</li>
            <li>Create more controllers and models</li>
        </ul>
    </div>
</div>';
    }

    private function generateEnvExample(): string
    {
        return '# Database Configuration
DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=

# Application Configuration
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Security
APP_KEY=your-secret-key-here';
    }

    private function generateReadme(string $projectName): string
    {
        return "# {$projectName}

A clean PHP application built with FireUp PHP Build.

## Features

- Clean MVC architecture
- PSR-4 autoloading
- Modern PHP 8.0+ features
- Responsive design with Tailwind CSS

## Installation

1. Clone this repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Start the development server: `php -S localhost:8000 -t public`

## Usage

- Controllers are in `src/Controllers/`
- Models are in `src/Models/`
- Templates are in `templates/`
- Configuration is in `config/`

## Development

This project was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License";
    }

    private function generateGitignore(): string
    {
        return '/vendor/
.env
.idea/
.vscode/
*.log
.DS_Store
Thumbs.db';
    }
} 