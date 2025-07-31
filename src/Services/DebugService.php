<?php

namespace FireUp\PhpBuild\Services;

use Symfony\Component\Process\Process;

class DebugService
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function analyzeAndFix(string $message): array
    {
        $message = strtolower($message);
        
        if (str_contains($message, 'syntax error') || str_contains($message, 'parse error')) {
            return $this->fixSyntaxErrors($message);
        }
        
        if (str_contains($message, 'undefined') || str_contains($message, 'not found')) {
            return $this->fixUndefinedErrors($message);
        }
        
        if (str_contains($message, 'database') || str_contains($message, 'connection')) {
            return $this->fixDatabaseErrors($message);
        }
        
        return $this->generalDebug($message);
    }

    public function validatePhpSyntax(string $filePath): array
    {
        $process = new Process(['php', '-l', $filePath]);
        $process->run();
        
        if ($process->isSuccessful()) {
            return [
                'valid' => true,
                'message' => 'PHP syntax is valid'
            ];
        }
        
        return [
            'valid' => false,
            'message' => $process->getErrorOutput(),
            'suggestions' => $this->generateSyntaxSuggestions($process->getErrorOutput())
        ];
    }

    private function fixSyntaxErrors(string $message): array
    {
        $fileName = $this->extractFileName($message);
        $filePath = $this->findFile($fileName);
        
        if (!$filePath) {
            return [
                'analysis' => "File '{$fileName}' not found for syntax analysis.",
                'suggestions' => ['Check if the file exists', 'Verify the file path']
            ];
        }

        $validation = $this->validatePhpSyntax($filePath);
        
        if ($validation['valid']) {
            return [
                'analysis' => "No syntax errors found in '{$fileName}'.",
                'suggestions' => ['The file appears to have valid PHP syntax']
            ];
        }

        $content = file_get_contents($filePath) ?: '';
        $fixedContent = $this->applySyntaxFixes($content, $validation['message']);
        
        if ($content !== $fixedContent) {
            file_put_contents($filePath, $fixedContent);
            
            return [
                'analysis' => "Fixed syntax errors in '{$fileName}': " . $validation['message'],
                'suggestions' => $validation['suggestions']
            ];
        }

        return [
            'analysis' => "Could not automatically fix syntax errors in '{$fileName}': " . $validation['message'],
            'suggestions' => $validation['suggestions']
        ];
    }

    private function fixUndefinedErrors(string $message): array
    {
        $fileName = $this->extractFileName($message);
        $filePath = $this->findFile($fileName);
        
        if (!$filePath) {
            return [
                'analysis' => "File '{$fileName}' not found for undefined error analysis.",
                'suggestions' => ['Check if the file exists', 'Verify the file path']
            ];
        }

        $content = file_get_contents($filePath) ?: '';
        $undefinedVars = $this->findUndefinedVariables($content);
        
        if (empty($undefinedVars)) {
            return [
                'analysis' => "No undefined variables found in '{$fileName}'.",
                'suggestions' => ['The file appears to have properly defined variables']
            ];
        }

        $fixedContent = $this->fixUndefinedVariables($content, $undefinedVars);
        
        if ($content !== $fixedContent) {
            file_put_contents($filePath, $fixedContent);
            
            return [
                'analysis' => "Fixed undefined variables in '{$fileName}': " . implode(', ', $undefinedVars),
                'suggestions' => [
                    'Variables are now properly defined',
                    'Check that all variables are initialized before use'
                ]
            ];
        }

        return [
            'analysis' => "Found undefined variables in '{$fileName}': " . implode(', ', $undefinedVars),
            'suggestions' => [
                'Define variables before using them',
                'Use isset() to check if variables exist',
                'Initialize variables with default values'
            ]
        ];
    }

    private function fixDatabaseErrors(string $message): array
    {
        return [
            'analysis' => 'Database connection issues detected.',
            'suggestions' => [
                'Check database configuration in config/database.php',
                'Verify database server is running',
                'Ensure database credentials are correct',
                'Check if database exists',
                'Verify network connectivity to database server'
            ]
        ];
    }

    private function generalDebug(string $message): array
    {
        return [
            'analysis' => 'General debugging analysis.',
            'suggestions' => [
                'Check PHP error logs',
                'Enable error reporting in development',
                'Use var_dump() or print_r() for debugging',
                'Check file permissions',
                'Verify all required dependencies are installed'
            ]
        ];
    }

    private function findFile(string $fileName): ?string
    {
        if (file_exists($this->projectRoot . '/' . $fileName)) {
            return $this->projectRoot . '/' . $fileName;
        }
        
        // Search in common directories
        $commonDirs = ['src', 'public', 'config', 'templates'];
        foreach ($commonDirs as $dir) {
            $path = $this->projectRoot . '/' . $dir . '/' . $fileName;
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    private function extractFileName(string $message): string
    {
        if (preg_match('/(?:in|file|from)\s+([a-zA-Z0-9_.\/-]+)/', $message, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/([a-zA-Z0-9_-]+\.(php|js|css|html))/', $message, $matches)) {
            return $matches[1];
        }
        
        return 'index.php';
    }

    private function generateSyntaxSuggestions(string $errorMessage): array
    {
        $suggestions = [];
        
        if (str_contains($errorMessage, 'unexpected')) {
            $suggestions[] = 'Check for missing or extra brackets, parentheses, or semicolons';
        }
        
        if (str_contains($errorMessage, 'unterminated')) {
            $suggestions[] = 'Check for unclosed quotes, strings, or comments';
        }
        
        if (str_contains($errorMessage, 'class')) {
            $suggestions[] = 'Verify class declaration syntax and namespace';
        }
        
        if (str_contains($errorMessage, 'function')) {
            $suggestions[] = 'Check function declaration syntax and parameters';
        }
        
        if (str_contains($errorMessage, 'variable')) {
            $suggestions[] = 'Ensure variables are properly declared and used';
        }
        
        return $suggestions;
    }

    private function applySyntaxFixes(string $content, string $errorMessage): string
    {
        // Common syntax fixes
        $content = str_replace('<?', '<?php', $content);
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);/', '$$1 = $2;', $content);
        
        // Fix missing semicolons
        $content = preg_replace('/([^;])\n\s*return/', '$1;\n        return', $content);
        
        // Fix unclosed strings
        $content = preg_replace('/"([^"]*)$/m', '$1"', $content);
        $content = preg_replace("/'([^']*)$/m", "$1'", $content);
        
        // Fix unclosed brackets
        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        if ($openBraces > $closeBraces) {
            $content .= str_repeat('}', $openBraces - $closeBraces);
        }
        
        return $content;
    }

    private function findUndefinedVariables(string $content): array
    {
        $undefinedVars = [];
        
        // Find variables that are used but not defined
        preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $content, $matches);
        $usedVars = array_unique($matches[1]);
        
        // Check for variable definitions
        foreach ($usedVars as $var) {
            if (!preg_match('/\$' . preg_quote($var) . '\s*=/', $content) && 
                !preg_match('/function\s+[^(]*\(\s*[^)]*\$' . preg_quote($var) . '/', $content) &&
                !preg_match('/foreach\s*\([^)]*\$' . preg_quote($var) . '/', $content)) {
                $undefinedVars[] = $var;
            }
        }
        
        return $undefinedVars;
    }

    private function fixUndefinedVariables(string $content, array $undefinedVars): string
    {
        foreach ($undefinedVars as $var) {
            // Add variable initialization at the beginning of functions/classes
            if (preg_match('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*\{/', $content, $matches)) {
                $functionName = $matches[1];
                $content = preg_replace(
                    '/function\s+' . preg_quote($functionName) . '\s*\([^)]*\)\s*\{/',
                    "function {$functionName}() {\n        \${$var} = null;",
                    $content
                );
            }
        }
        
        return $content;
    }
} 