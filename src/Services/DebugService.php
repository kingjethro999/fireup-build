<?php

namespace FireUp\PhpBuild\Services;

use Exception;

class DebugService
{
    public function handleDebugRequest(array $instructions): string
    {
        $explanation = $instructions['explanation'] ?? 'Debugging...';
        $files = $instructions['files'] ?? [];
        
        $result = "🔧 {$explanation}\n\n";
        
        if (!empty($files)) {
            $result .= "📁 Files processed:\n";
            foreach ($files as $file) {
                $result .= "• {$file['path']}: {$file['description']}\n";
            }
            $result .= "\n";
        }
        
        return $result;
    }

    public function analyzeAndFix(string $message): array
    {
        // Basic syntax checking
        $syntaxErrors = $this->checkSyntax();
        $suggestions = $this->generateSuggestions($message);
        
        return [
            'syntax_errors' => $syntaxErrors,
            'suggestions' => $suggestions,
            'content' => $this->formatDebugOutput($syntaxErrors, $suggestions)
        ];
    }

    private function checkSyntax(): array
    {
        $errors = [];
        
        // Check PHP files for syntax errors
        $phpFiles = $this->findPhpFiles();
        
        foreach ($phpFiles as $file) {
            $output = [];
            $returnCode = 0;
            
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $errors[] = [
                    'file' => $file,
                    'error' => implode("\n", $output)
                ];
            }
        }
        
        return $errors;
    }

    private function findPhpFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(getcwd(), \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                
                // Skip vendor directory
                if (!str_starts_with($relativePath, 'vendor/')) {
                    $files[] = $relativePath;
                }
            }
        }
        
        return $files;
    }

    private function generateSuggestions(string $message): array
    {
        $suggestions = [];
        $lowerMessage = strtolower($message);
        
        if (str_contains($lowerMessage, 'syntax error')) {
            $suggestions[] = "Check for missing semicolons, brackets, or quotes";
            $suggestions[] = "Verify PHP syntax with 'php -l filename.php'";
        }
        
        if (str_contains($lowerMessage, 'class not found')) {
            $suggestions[] = "Check if the class file exists and is properly autoloaded";
            $suggestions[] = "Run 'composer dump-autoload' to regenerate autoloader";
        }
        
        if (str_contains($lowerMessage, 'function not found')) {
            $suggestions[] = "Check if the function is defined or included";
            $suggestions[] = "Verify function name spelling and namespace";
        }
        
        if (str_contains($lowerMessage, 'database') || str_contains($lowerMessage, 'mysql')) {
            $suggestions[] = "Check database connection settings";
            $suggestions[] = "Verify database server is running";
        }
        
        return $suggestions;
    }

    private function formatDebugOutput(array $syntaxErrors, array $suggestions): string
    {
        $output = "";
        
        if (!empty($syntaxErrors)) {
            $output .= "❌ Syntax Errors Found:\n";
            foreach ($syntaxErrors as $error) {
                $output .= "• {$error['file']}: {$error['error']}\n";
            }
            $output .= "\n";
        } else {
            $output .= "✅ No syntax errors found\n\n";
        }
        
        if (!empty($suggestions)) {
            $output .= "💡 Suggestions:\n";
            foreach ($suggestions as $suggestion) {
                $output .= "• {$suggestion}\n";
            }
            $output .= "\n";
        }
        
        return $output;
    }
} 