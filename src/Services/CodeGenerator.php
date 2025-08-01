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
        // First, analyze existing files to understand current state
        $existingFiles = $this->analyzeExistingFiles();
        $projectType = $this->determineProjectType($message, $existingFiles);
        
        // Use current directory - don't create subdirectories
        $projectPath = $this->projectRoot;
        $projectName = basename($this->projectRoot);
        
        // Generate files based on what's needed and what already exists
        $files = $this->generateIntelligentStructure($message, $existingFiles, $projectType);
        
        $createdFiles = [];
        foreach ($files as $filePath => $content) {
            $fullPath = $projectPath . '/' . $filePath;
            $this->filesystem->mkdir(dirname($fullPath));
            $this->filesystem->dumpFile($fullPath, $content);
            $createdFiles[] = ['path' => $filePath, 'content' => $content];
        }

        return [
            'content' => "✅ {$projectType} structure created successfully!\n\n📁 Files created/updated:\n" . implode("\n", array_map(fn($file) => "• {$file['path']}", $createdFiles)),
            'files' => $createdFiles
        ];
    }

    private function analyzeExistingFiles(): array
    {
        $existing = [];
        
        // Check for common PHP project files
        $commonFiles = [
            'composer.json', 'index.php', 'public/index.php', 
            'src/', 'app/', 'controllers/', 'models/', 'views/',
            '.env', '.env.example', 'README.md'
        ];
        
        foreach ($commonFiles as $file) {
            $fullPath = $this->projectRoot . '/' . $file;
            if ($this->filesystem->exists($fullPath)) {
                $existing[] = $file;
            }
        }
        
        return $existing;
    }

    private function determineProjectType(string $message, array $existingFiles): string
    {
        $message = strtolower($message);
        
        if (str_contains($message, 'landing page') || str_contains($message, 'landing')) return 'landing page';
        if (str_contains($message, 'blog')) return 'blog';
        if (str_contains($message, 'website')) return 'website';
        if (str_contains($message, 'api')) return 'api';
        if (str_contains($message, 'ecommerce') || str_contains($message, 'shop')) return 'ecommerce';
        if (str_contains($message, 'cms')) return 'cms';
        
        // Default to MVC application
        return 'mvc application';
    }

    private function generateIntelligentStructure(string $message, array $existingFiles, string $projectType): array
    {
        $files = [];
        
        // Always ensure basic structure exists
        if (!in_array('composer.json', $existingFiles)) {
            $files['composer.json'] = $this->generateComposerJson(basename($this->projectRoot));
        }
        
        if (!in_array('public/index.php', $existingFiles)) {
            $files['public/index.php'] = $this->generateIndexPhp();
        }
        
        // Generate structure based on project type
        switch ($projectType) {
            case 'landing page':
                $files = array_merge($files, $this->generateLandingPageStructure(basename($this->projectRoot)));
                break;
            case 'blog':
                $files = array_merge($files, $this->generateBlogStructure(basename($this->projectRoot)));
                break;
            case 'website':
                $files = array_merge($files, $this->generateWebsiteStructure(basename($this->projectRoot)));
                break;
            case 'api':
                $files = array_merge($files, $this->generateApiStructure(basename($this->projectRoot)));
                break;
            default:
                $files = array_merge($files, $this->generateMvcStructure(basename($this->projectRoot)));
        }
        
        return $files;
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

    // Intelligent structure generation methods
    private function generateLandingPageStructure(string $projectName): array
    {
        return [
            'src/App.php' => $this->generateLandingPageAppClass(),
            'src/Controllers/LandingController.php' => $this->generateLandingController(),
            'templates/layout.html' => $this->generateLandingPageLayoutTemplate(),
            'templates/home.html' => $this->generateLandingPageTemplate(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateLandingPageReadme($projectName)
        ];
    }

    private function generateBlogStructure(string $projectName): array
    {
        return [
            'src/App.php' => $this->generateBlogAppClass(),
            'src/Controllers/BlogController.php' => $this->generateBlogController(),
            'src/Controllers/PostController.php' => $this->generatePostController(),
            'src/Models/Post.php' => $this->generatePostModel(),
            'src/Models/User.php' => $this->generateUserModel(),
            'config/database.php' => $this->generateDatabaseConfig(),
            'templates/layout.html' => $this->generateBlogLayoutTemplate(),
            'templates/home.html' => $this->generateBlogHomeTemplate(),
            'templates/post.html' => $this->generatePostTemplate(),
            'templates/admin.html' => $this->generateAdminTemplate(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateBlogReadme($projectName)
        ];
    }

    private function generateWebsiteStructure(string $projectName): array
    {
        return [
            'src/App.php' => $this->generateWebsiteAppClass(),
            'src/Controllers/PageController.php' => $this->generatePageController(),
            'src/Models/Page.php' => $this->generatePageModel(),
            'config/database.php' => $this->generateDatabaseConfig(),
            'templates/layout.html' => $this->generateWebsiteLayoutTemplate(),
            'templates/home.html' => $this->generateWebsiteHomeTemplate(),
            'templates/about.html' => $this->generateAboutTemplate(),
            'templates/contact.html' => $this->generateContactTemplate(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateWebsiteReadme($projectName)
        ];
    }

    private function generateApiStructure(string $projectName): array
    {
        return [
            'src/App.php' => $this->generateApiAppClass(),
            'src/Controllers/ApiController.php' => $this->generateApiController(),
            'src/Models/User.php' => $this->generateUserModel(),
            'config/database.php' => $this->generateDatabaseConfig(),
            'src/Middleware/AuthMiddleware.php' => $this->generateAuthMiddleware(),
            'src/Middleware/CorsMiddleware.php' => $this->generateCorsMiddleware(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateApiReadme($projectName)
        ];
    }

    private function generateMvcStructure(string $projectName): array
    {
        return [
            'src/App.php' => $this->generateAppClass(),
            'src/Controllers/HomeController.php' => $this->generateHomeController(),
            'src/Models/User.php' => $this->generateUserModel(),
            'config/database.php' => $this->generateDatabaseConfig(),
            'templates/layout.html' => $this->generateLayoutTemplate(),
            'templates/home.html' => $this->generateHomeTemplate(),
            '.env.example' => $this->generateEnvExample(),
            'README.md' => $this->generateReadme($projectName)
        ];
    }

    // Blog-specific methods
    private function generateBlogAppClass(): string
    {
        return '<?php

namespace App;

class App
{
    public function __construct()
    {
        // Initialize blog application
    }

    public function run()
    {
        // Handle blog routing
        $this->handleRoutes();
    }

    private function handleRoutes()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";
        
        switch ($path) {
            case "/":
                $this->showHome();
                break;
            case "/post":
                $this->showPost();
                break;
            case "/admin":
                $this->showAdmin();
                break;
            default:
                $this->show404();
        }
    }

    private function showHome()
    {
        include __DIR__ . "/../templates/home.html";
    }

    private function showPost()
    {
        include __DIR__ . "/../templates/post.html";
    }

    private function showAdmin()
    {
        include __DIR__ . "/../templates/admin.html";
    }

    private function show404()
    {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }
}';
    }

    private function generateBlogController(): string
    {
        return '<?php

namespace App\Controllers;

class BlogController
{
    public function index()
    {
        // Show blog home page
        $posts = $this->getPosts();
        include __DIR__ . "/../../templates/home.html";
    }

    public function show($id)
    {
        // Show individual post
        $post = $this->getPost($id);
        include __DIR__ . "/../../templates/post.html";
    }

    private function getPosts()
    {
        // Mock data - replace with database
        return [
            ["id" => 1, "title" => "Welcome to My Blog", "excerpt" => "This is my first blog post...", "date" => "2024-01-01"],
            ["id" => 2, "title" => "Getting Started with PHP", "excerpt" => "Learn the basics of PHP...", "date" => "2024-01-02"]
        ];
    }

    private function getPost($id)
    {
        // Mock data - replace with database
        return [
            "id" => $id,
            "title" => "Sample Blog Post",
            "content" => "This is the full content of the blog post...",
            "date" => "2024-01-01",
            "author" => "Admin"
        ];
    }
}';
    }

    private function generatePostController(): string
    {
        return '<?php

namespace App\Controllers;

use App\Models\Post;

class PostController
{
    public function index()
    {
        $posts = Post::all();
        include __DIR__ . "/../../templates/posts.html";
    }

    public function show($id)
    {
        $post = Post::find($id);
        include __DIR__ . "/../../templates/post.html";
    }

    public function create()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $post = new Post();
            $post->title = $_POST["title"];
            $post->content = $_POST["content"];
            $post->save();
            
            header("Location: /admin");
            exit;
        }
        
        include __DIR__ . "/../../templates/create-post.html";
    }
}';
    }

    private function generatePostModel(): string
    {
        return '<?php

namespace App\Models;

class Post
{
    public $id;
    public $title;
    public $content;
    public $author;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function all()
    {
        // Mock data - replace with database
        return [
            new self(["id" => 1, "title" => "Welcome to My Blog", "content" => "This is my first blog post...", "author" => "Admin", "created_at" => "2024-01-01"]),
            new self(["id" => 2, "title" => "Getting Started with PHP", "content" => "Learn the basics of PHP...", "author" => "Admin", "created_at" => "2024-01-02"])
        ];
    }

    public static function find($id)
    {
        // Mock data - replace with database
        return new self([
            "id" => $id,
            "title" => "Sample Blog Post",
            "content" => "This is the full content of the blog post...",
            "author" => "Admin",
            "created_at" => "2024-01-01"
        ]);
    }

    public function save()
    {
        // Mock save - replace with database
        $this->id = rand(1, 1000);
        $this->created_at = date("Y-m-d H:i:s");
        return true;
    }
}';
    }

    private function generateBlogLayoutTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? "My Blog" ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="/" class="text-2xl font-bold text-gray-800">My Blog</a>
                <div class="space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-800">Home</a>
                    <a href="/admin" class="text-gray-600 hover:text-gray-800">Admin</a>
                </div>
            </div>
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

    private function generateBlogHomeTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Welcome to My Blog</h1>
    
    <div class="grid gap-6">
        <article class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-2">Welcome to My Blog</h2>
            <p class="text-gray-600 mb-4">This is my first blog post. Welcome to my new blog built with FireUp PHP Build!</p>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">January 1, 2024</span>
                <a href="/post/1" class="text-blue-600 hover:text-blue-800">Read more →</a>
            </div>
        </article>
        
        <article class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-2">Getting Started with PHP</h2>
            <p class="text-gray-600 mb-4">Learn the basics of PHP programming and web development.</p>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">January 2, 2024</span>
                <a href="/post/2" class="text-blue-600 hover:text-blue-800">Read more →</a>
            </div>
        </article>
    </div>
</div>';
    }

    private function generatePostTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <article class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Sample Blog Post</h1>
        <div class="flex items-center text-gray-600 mb-6">
            <span>By Admin</span>
            <span class="mx-2">•</span>
            <span>January 1, 2024</span>
        </div>
        <div class="prose max-w-none">
            <p>This is the full content of the blog post. You can write your blog content here.</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            <h2>Subheading</h2>
            <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        </div>
    </article>
</div>';
    }

    private function generateAdminTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Blog Admin</h1>
        
        <div class="mb-6">
            <a href="/admin/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create New Post</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">Welcome to My Blog</td>
                        <td class="px-6 py-4 whitespace-nowrap">2024-01-01</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/post/1" class="text-blue-600 hover:text-blue-800">View</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>';
    }

    private function generateBlogReadme(string $projectName): string
    {
        return "# {$projectName} - Blog

A modern blog built with FireUp PHP Build.

## Features

- Clean blog structure
- Post management
- Admin interface
- Responsive design with Tailwind CSS
- MVC architecture

## Installation

1. Clone this repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Start the development server: `php -S localhost:8000 -t public`

## Usage

- View blog posts at `/`
- Read individual posts at `/post/{id}`
- Access admin panel at `/admin`
- Create new posts in the admin panel

## Structure

- `src/Controllers/` - Blog and Post controllers
- `src/Models/` - Post and User models
- `templates/` - Blog templates
- `public/` - Web root

## Development

This blog was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License";
    }

    // Website-specific methods
    private function generateWebsiteAppClass(): string
    {
        return '<?php

namespace App;

class App
{
    public function __construct()
    {
        // Initialize website application
    }

    public function run()
    {
        // Handle website routing
        $this->handleRoutes();
    }

    private function handleRoutes()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";
        
        switch ($path) {
            case "/":
                $this->showHome();
                break;
            case "/about":
                $this->showAbout();
                break;
            case "/contact":
                $this->showContact();
                break;
            default:
                $this->show404();
        }
    }

    private function showHome()
    {
        include __DIR__ . "/../templates/home.html";
    }

    private function showAbout()
    {
        include __DIR__ . "/../templates/about.html";
    }

    private function showContact()
    {
        include __DIR__ . "/../templates/contact.html";
    }

    private function show404()
    {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }
}';
    }

    private function generatePageController(): string
    {
        return '<?php

namespace App\Controllers;

class PageController
{
    public function home()
    {
        include __DIR__ . "/../../templates/home.html";
    }

    public function about()
    {
        include __DIR__ . "/../../templates/about.html";
    }

    public function contact()
    {
        include __DIR__ . "/../../templates/contact.html";
    }
}';
    }

    private function generatePageModel(): string
    {
        return '<?php

namespace App\Models;

class Page
{
    public $id;
    public $title;
    public $content;
    public $slug;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function findBySlug($slug)
    {
        // Mock data - replace with database
        return new self([
            "id" => 1,
            "title" => "Sample Page",
            "content" => "This is the page content...",
            "slug" => $slug,
            "created_at" => "2024-01-01"
        ]);
    }
}';
    }

    private function generateWebsiteLayoutTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? "My Website" ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="/" class="text-2xl font-bold text-gray-800">My Website</a>
                <div class="space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-800">Home</a>
                    <a href="/about" class="text-gray-600 hover:text-gray-800">About</a>
                    <a href="/contact" class="text-gray-600 hover:text-gray-800">Contact</a>
                </div>
            </div>
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

    private function generateWebsiteHomeTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Welcome to My Website</h1>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <p class="text-lg text-gray-600 mb-6">
            This is a modern website built with FireUp PHP Build. 
            It features a clean, responsive design and is ready for your content.
        </p>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-blue-800 mb-2">Feature 1</h3>
                <p class="text-blue-600">Description of your first feature.</p>
            </div>
            
            <div class="bg-green-50 p-6 rounded-lg">
                <h3 class="text-xl font-bold text-green-800 mb-2">Feature 2</h3>
                <p class="text-green-600">Description of your second feature.</p>
            </div>
        </div>
    </div>
</div>';
    }

    private function generateAboutTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">About Us</h1>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <p class="text-lg text-gray-600 mb-6">
            This is the about page of your website. You can add information about your company, 
            team, or project here.
        </p>
        
        <div class="bg-gray-50 p-6 rounded-lg">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Mission</h2>
            <p class="text-gray-600">
                To provide excellent web solutions using modern PHP development practices.
            </p>
        </div>
    </div>
</div>';
    }

    private function generateContactTemplate(): string
    {
        return '<div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Contact Us</h1>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <form class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                Send Message
            </button>
        </form>
    </div>
</div>';
    }

    private function generateWebsiteReadme(string $projectName): string
    {
        return "# {$projectName} - Website

A modern website built with FireUp PHP Build.

## Features

- Clean website structure
- Responsive design with Tailwind CSS
- Multiple pages (Home, About, Contact)
- MVC architecture

## Installation

1. Clone this repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Start the development server: `php -S localhost:8000 -t public`

## Usage

- Home page at `/`
- About page at `/about`
- Contact page at `/contact`

## Structure

- `src/Controllers/` - Page controllers
- `src/Models/` - Page models
- `templates/` - Website templates
- `public/` - Web root

## Development

This website was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License";
    }

    // API-specific methods
    private function generateApiAppClass(): string
    {
        return '<?php

namespace App;

class App
{
    public function __construct()
    {
        // Initialize API application
        header("Content-Type: application/json");
    }

    public function run()
    {
        // Handle API routing
        $this->handleRoutes();
    }

    private function handleRoutes()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";
        $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
        
        switch ($path) {
            case "/api/users":
                $this->handleUsers($method);
                break;
            case "/api/health":
                $this->healthCheck();
                break;
            default:
                $this->notFound();
        }
    }

    private function handleUsers($method)
    {
        switch ($method) {
            case "GET":
                echo json_encode(["users" => [["id" => 1, "name" => "John Doe"]]]);
                break;
            case "POST":
                $data = json_decode(file_get_contents("php://input"), true);
                echo json_encode(["message" => "User created", "data" => $data]);
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
        }
    }

    private function healthCheck()
    {
        echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s")]);
    }

    private function notFound()
    {
        http_response_code(404);
        echo json_encode(["error" => "Not found"]);
    }
}';
    }

    private function generateApiController(): string
    {
        return '<?php

namespace App\Controllers;

class ApiController
{
    public function index()
    {
        return ["message" => "API is running"];
    }

    public function users()
    {
        return [
            ["id" => 1, "name" => "John Doe", "email" => "john@example.com"],
            ["id" => 2, "name" => "Jane Smith", "email" => "jane@example.com"]
        ];
    }

    public function createUser()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        // Process user creation
        return ["message" => "User created successfully", "data" => $data];
    }
}';
    }

    private function generateAuthMiddleware(): string
    {
        return '<?php

namespace App\Middleware;

class AuthMiddleware
{
    public function handle($request, $next)
    {
        // Check authentication
        $token = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }
        
        // Validate token (implement your logic here)
        if (!$this->validateToken($token)) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid token"]);
            return;
        }
        
        return $next($request);
    }

    private function validateToken($token)
    {
        // Implement token validation logic
        return true; // Mock validation
    }
}';
    }

    private function generateCorsMiddleware(): string
    {
        return '<?php

namespace App\Middleware;

class CorsMiddleware
{
    public function handle($request, $next)
    {
        // Set CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            http_response_code(200);
            exit;
        }
        
        return $next($request);
    }
}';
    }

    private function generateApiReadme(string $projectName): string
    {
        return "# {$projectName} - API

A RESTful API built with FireUp PHP Build.

## Features

- RESTful API endpoints
- JSON responses
- Authentication middleware
- CORS support
- Clean architecture

## Installation

1. Clone this repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Start the development server: `php -S localhost:8000 -t public`

## API Endpoints

- `GET /api/health` - Health check
- `GET /api/users` - Get all users
- `POST /api/users` - Create a new user

## Usage

```bash
# Health check
curl http://localhost:8000/api/health

# Get users
curl http://localhost:8000/api/users

# Create user
curl -X POST http://localhost:8000/api/users -H "Content-Type: application/json" -d \'{"name": "John Doe", "email": "john@example.com"}\'
```

## Structure

- `src/Controllers/` - API controllers
- `src/Models/` - Data models
- `src/Middleware/` - Authentication and CORS middleware
- `public/` - Web root

## Development

This API was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License";
    }

    // Landing page specific methods
    private function generateLandingPageAppClass(): string
    {
        return '<?php

namespace App;

class App
{
    public function __construct()
    {
        // Initialize landing page application
    }

    public function run()
    {
        // Handle landing page routing
        $this->handleRoutes();
    }

    private function handleRoutes()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";
        
        switch ($path) {
            case "/":
                $this->showLandingPage();
                break;
            default:
                $this->show404();
        }
    }

    private function showLandingPage()
    {
        include __DIR__ . "/../templates/home.html";
    }

    private function show404()
    {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
    }
}';
    }

    private function generateLandingController(): string
    {
        return '<?php

namespace App\Controllers;

class LandingController
{
    public function index()
    {
        // Show landing page
        include __DIR__ . "/../../templates/home.html";
    }
}';
    }

    private function generateLandingPageLayoutTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? "Landing Page" ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="/" class="text-2xl font-bold text-gray-800">Your Brand</a>
                <div class="space-x-6">
                    <a href="#features" class="text-gray-600 hover:text-gray-800">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-gray-800">Pricing</a>
                    <a href="#contact" class="text-gray-600 hover:text-gray-800">Contact</a>
                    <a href="#cta" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Get Started</a>
                </div>
            </div>
        </div>
    </nav>
    
    <main>
        <?= $content ?? "" ?>
    </main>
    
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 Your Brand. All rights reserved.</p>
            <p class="mt-2 text-gray-400">Built with FireUp PHP Build</p>
        </div>
    </footer>
</body>
</html>';
    }

    private function generateLandingPageTemplate(): string
    {
        return '<div class="bg-white">
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div class="container mx-auto px-4 py-20">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-6">Welcome to Your Amazing Product</h1>
                <p class="text-xl mb-8 max-w-2xl mx-auto">
                    Transform your business with our innovative solution. 
                    Simple, powerful, and designed for success.
                </p>
                <div class="space-x-4">
                    <a href="#cta" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">
                        Get Started Free
                    </a>
                    <a href="#demo" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600">
                        Watch Demo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-20">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Why Choose Us?</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Lightning Fast</h3>
                    <p class="text-gray-600">Experience blazing fast performance that keeps your users engaged.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Reliable</h3>
                    <p class="text-gray-600">Built with reliability in mind, ensuring your business never stops.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Secure</h3>
                    <p class="text-gray-600">Enterprise-grade security to protect your data and your users.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div id="cta" class="bg-gray-100 py-20">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-xl text-gray-600 mb-8">Join thousands of satisfied customers today.</p>
            <a href="#" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700">
                Start Your Free Trial
            </a>
        </div>
    </div>
</div>';
    }

    private function generateLandingPageReadme(string $projectName): string
    {
        return "# {$projectName} - Landing Page

A modern, responsive landing page built with FireUp PHP Build.

## Features

- Modern, responsive design
- Hero section with call-to-action
- Features showcase
- Clean, professional layout
- Built with Tailwind CSS

## Installation

1. Clone this repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Start the development server: `php -S localhost:8000 -t public`

## Usage

- Landing page is served at `/`
- Responsive design works on all devices
- Easy to customize content and styling

## Structure

- `src/Controllers/` - Landing page controller
- `templates/` - Landing page templates
- `public/` - Web root

## Customization

Edit the templates to match your brand:
- Update colors in the CSS classes
- Change the hero text and call-to-action
- Modify the features section
- Add your own content and sections

## Development

This landing page was created with FireUp PHP Build - an interactive PHP development tool.

## License

MIT License";
    }
} 