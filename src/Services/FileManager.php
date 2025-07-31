<?php

namespace FireUp\PhpBuild\Services;

use Symfony\Component\Finder\Finder;

class FileManager
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function processFileOperation(string $message): array
    {
        $message = strtolower($message);
        
        if (str_contains($message, 'edit') || str_contains($message, 'modify')) {
            return $this->editFile($message);
        }
        
        if (str_contains($message, 'create file')) {
            return $this->createFile($message);
        }
        
        if (str_contains($message, 'delete') || str_contains($message, 'remove')) {
            return $this->deleteFile($message);
        }
        
        return [
            'message' => 'I can help you edit, create, or delete files. Please be more specific.',
            'changes' => []
        ];
    }

    public function editFile(string $message): array
    {
        $fileName = $this->extractFileName($message);
        $filePath = $this->findFile($fileName);
        
        if (!$filePath) {
            return [
                'message' => "File '{$fileName}' not found.",
                'changes' => []
            ];
        }

        $content = file_get_contents($filePath) ?: '';
        $newContent = $this->applyEdits($content, $message);
        
        if ($content !== $newContent) {
            file_put_contents($filePath, $newContent);
            
            return [
                'message' => "File '{$fileName}' updated successfully.",
                'changes' => [
                    "Modified: {$fileName}",
                    "Applied requested changes"
                ]
            ];
        }

        return [
            'message' => "No changes were needed for '{$fileName}'.",
            'changes' => []
        ];
    }

    public function createFile(string $message): array
    {
        $fileName = $this->extractFileName($message);
        $filePath = $this->projectRoot . '/' . $fileName;
        
        if (file_exists($filePath)) {
            return [
                'message' => "File '{$fileName}' already exists.",
                'changes' => []
            ];
        }

        $content = $this->generateFileContent($fileName, $message);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $content);

        return [
            'message' => "File '{$fileName}' created successfully.",
            'changes' => [
                "Created: {$fileName}",
                "Generated appropriate content"
            ]
        ];
    }

    public function deleteFile(string $message): array
    {
        $fileName = $this->extractFileName($message);
        $filePath = $this->findFile($fileName);
        
        if (!$filePath) {
            return [
                'message' => "File '{$fileName}' not found.",
                'changes' => []
            ];
        }

        unlink($filePath);

        return [
            'message' => "File '{$fileName}' deleted successfully.",
            'changes' => [
                "Deleted: {$fileName}"
            ]
        ];
    }

    public function findFile(string $fileName): ?string
    {
        $finder = new Finder();
        $finder->files()->name("*{$fileName}*")->in($this->projectRoot);
        
        foreach ($finder as $file) {
            return $file->getRealPath();
        }
        
        return null;
    }

    private function extractFileName(string $message): string
    {
        // Extract filename from message
        if (preg_match('/(?:edit|modify|create|delete|remove)\s+(?:file\s+)?([a-zA-Z0-9_.\/-]+)/', $message, $matches)) {
            return $matches[1];
        }
        
        // Look for common file patterns
        if (preg_match('/([a-zA-Z0-9_-]+\.(php|js|css|html|json|md|txt))/', $message, $matches)) {
            return $matches[1];
        }
        
        return 'index.php';
    }

    private function applyEdits(string $content, string $message): string
    {
        $newContent = $content;
        
        // Handle different types of edits
        if (str_contains($message, 'add method') || str_contains($message, 'add function')) {
            $newContent = $this->addMethod($content, $message);
        }
        
        if (str_contains($message, 'add property') || str_contains($message, 'add field')) {
            $newContent = $this->addProperty($content, $message);
        }
        
        if (str_contains($message, 'fix error') || str_contains($message, 'debug')) {
            $newContent = $this->fixErrors($content, $message);
        }
        
        if (str_contains($message, 'update') || str_contains($message, 'change')) {
            $newContent = $this->updateContent($content, $message);
        }
        
        return $newContent;
    }

    private function addMethod(string $content, string $message): string
    {
        $methodName = $this->extractMethodName($message);
        $methodContent = $this->generateMethodContent($methodName);
        
        // Find the last closing brace and add method before it
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false) {
            $content = substr($content, 0, $lastBracePos) . "\n    " . $methodContent . "\n}";
        }
        
        return $content;
    }

    private function addProperty(string $content, string $message): string
    {
        $propertyName = $this->extractPropertyName($message);
        $propertyContent = "    private \${$propertyName};\n";
        
        // Find the class opening and add property after it
        $classPos = strpos($content, 'class');
        if ($classPos !== false) {
            $bracePos = strpos($content, '{', $classPos);
            if ($bracePos !== false) {
                $content = substr($content, 0, $bracePos + 1) . "\n" . $propertyContent . substr($content, $bracePos + 1);
            }
        }
        
        return $content;
    }

    private function fixErrors(string $content, string $message): string
    {
        // Common PHP error fixes
        $content = str_replace('<?', '<?php', $content);
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);/', '$$1 = $2;', $content);
        
        // Fix missing semicolons
        $content = preg_replace('/([^;])\n\s*return/', '$1;\n        return', $content);
        
        return $content;
    }

    private function updateContent(string $content, string $message): string
    {
        // Simple content replacement based on message
        if (str_contains($message, 'namespace')) {
            $namespace = $this->extractNamespace($message);
            $content = preg_replace('/namespace\s+[^;]+;/', "namespace {$namespace};", $content);
        }
        
        if (str_contains($message, 'class name')) {
            $className = $this->extractClassName($message);
            $content = preg_replace('/class\s+[a-zA-Z_][a-zA-Z0-9_]*/', "class {$className}", $content);
        }
        
        return $content;
    }

    private function generateFileContent(string $fileName, string $message): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'php':
                return $this->generatePhpContent($fileName, $message);
            case 'js':
                return $this->generateJsContent($fileName, $message);
            case 'css':
                return $this->generateCssContent($fileName, $message);
            case 'html':
                return $this->generateHtmlContent($fileName, $message);
            case 'json':
                return $this->generateJsonContent($fileName, $message);
            default:
                return "// Generated file: {$fileName}\n// TODO: Add content";
        }
    }

    private function generatePhpContent(string $fileName, string $message): string
    {
        $className = pathinfo($fileName, PATHINFO_FILENAME);
        $className = ucfirst($className);
        
        return "<?php

namespace App;

class {$className}
{
    public function __construct()
    {
        // TODO: Initialize {$className}
    }
    
    public function index()
    {
        // TODO: Implement index method
        return 'Hello from {$className}!';
    }
}";
    }

    private function generateJsContent(string $fileName, string $message): string
    {
        return "// {$fileName}
// Generated JavaScript file

(function() {
    'use strict';
    
    // TODO: Add JavaScript functionality
    console.log('{$fileName} loaded');
})();";
    }

    private function generateCssContent(string $fileName, string $message): string
    {
        return "/* {$fileName} */
/* Generated CSS file */

/* TODO: Add your styles here */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}";
    }

    private function generateHtmlContent(string $fileName, string $message): string
    {
        return "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$fileName}</title>
    <link rel=\"stylesheet\" href=\"styles.css\">
</head>
<body>
    <div class=\"container\">
        <h1>Welcome to {$fileName}</h1>
        <p>This is a generated HTML file.</p>
    </div>
    
    <script src=\"script.js\"></script>
</body>
</html>";
    }

    private function generateJsonContent(string $fileName, string $message): string
    {
        return "{
    \"name\": \"{$fileName}\",
    \"description\": \"Generated JSON file\",
    \"version\": \"1.1.0\",
    \"data\": {}
}";
    }

    private function extractMethodName(string $message): string
    {
        if (preg_match('/(?:add|create)\s+(?:method|function)\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'newMethod';
    }

    private function extractPropertyName(string $message): string
    {
        if (preg_match('/(?:add|create)\s+(?:property|field)\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'newProperty';
    }

    private function extractNamespace(string $message): string
    {
        if (preg_match('/namespace\s+([a-zA-Z_][a-zA-Z0-9_\\\\]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'App';
    }

    private function extractClassName(string $message): string
    {
        if (preg_match('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $message, $matches)) {
            return $matches[1];
        }
        return 'MyClass';
    }

    private function generateMethodContent(string $methodName): string
    {
        return "public function {$methodName}()
    {
        // TODO: Implement {$methodName} method
        return '{$methodName} called';
    }";
    }
} 