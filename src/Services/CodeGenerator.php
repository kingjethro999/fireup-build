<?php

namespace FireUp\PhpBuild\Services;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Exception;

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

    /**
     * Main method for analyzing project and providing context to AI
     */
    public function analyzeAndSuggest(string $message): array
    {
        $analysis = $this->analyzeExistingFiles();
        $structure = $analysis['structure'];
        
        return [
            'structure' => $structure,
            'files' => $analysis['files'],
            'fileContents' => $this->getRelevantFileContents($analysis['contents']),
            'suggestions' => $this->generateContextualSuggestions($structure, $message)
        ];
    }

    /**
     * Execute file operations based on AI instructions
     */
    public function executeFileOperations(array $files): array
    {
        $createdFiles = [];
        $updatedFiles = [];
        $errors = [];

        foreach ($files as $fileInfo) {
            try {
                $path = $fileInfo['path'];
                $content = $fileInfo['content'];
                $fullPath = $this->projectRoot . '/' . $path;
                
                // Create directory if needed
                $directory = dirname($fullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                // Check if file exists
                $exists = file_exists($fullPath);
                
                // Write file
                file_put_contents($fullPath, $content);
                
                if ($exists) {
                    $updatedFiles[] = $path;
                } else {
                    $createdFiles[] = $path;
                }
                
            } catch (Exception $e) {
                $errors[] = "Failed to create/update {$fileInfo['path']}: " . $e->getMessage();
            }
        }

        return [
            'created' => $createdFiles,
            'updated' => $updatedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Get current project structure and files
     */
    private function analyzeExistingFiles(): array
    {
        $existing = [];
        $fileContents = [];
        
        try {
            $finder = new Finder();
            $finder->files()
                  ->in($this->projectRoot)
                  ->notPath('vendor')
                  ->notPath('.git')
                  ->notPath('node_modules')
                  ->size('< 100K'); // Don't read huge files
            
            foreach ($finder as $file) {
                $relativePath = $file->getRelativePathname();
                $existing[] = $relativePath;
                
                // Read important files for context
                if ($this->isImportantFile($relativePath)) {
                    try {
                        $content = file_get_contents($file->getRealPath());
                        $fileContents[$relativePath] = [
                            'content' => $content,
                            'size' => strlen($content),
                            'type' => $file->getExtension(),
                            'modified' => filemtime($file->getRealPath())
                        ];
                    } catch (Exception $e) {
                        // Skip files that can't be read
                    }
                }
            }
        } catch (Exception $e) {
            // Fallback to basic directory scan
            $existing = $this->basicDirectoryScan();
        }
        
        return [
            'files' => $existing,
            'contents' => $fileContents,
            'structure' => $this->analyzeProjectStructure($existing)
        ];
    }

    /**
     * Determine if a file is important for AI context
     */
    private function isImportantFile(string $path): bool
    {
        $importantFiles = [
            'composer.json', 'package.json', '.env', '.env.example',
            'README.md', 'index.php', 'app.php'
        ];
        
        $importantExtensions = ['php', 'js', 'html', 'css'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return in_array(basename($path), $importantFiles) || 
               in_array($extension, $importantExtensions);
    }

    /**
     * Get relevant file contents for AI context (truncated if too long)
     */
    private function getRelevantFileContents(array $contents): array
    {
        $relevant = [];
        
        foreach ($contents as $path => $info) {
            // Truncate long files to first 1000 characters for context
            $content = $info['content'];
            if (strlen($content) > 1000) {
                $content = substr($content, 0, 1000) . "\n... [file truncated for context]";
            }
            
            $relevant[$path] = $content;
        }
        
        return $relevant;
    }

    /**
     * Analyze project structure
     */
    private function analyzeProjectStructure(array $files): array
    {
        $structure = [
            'hasComposer' => false,
            'hasPublic' => false,
            'hasSrc' => false,
            'hasControllers' => false,
            'hasModels' => false,
            'hasViews' => false,
            'hasConfig' => false,
            'hasDatabase' => false,
            'phpFiles' => [],
            'htmlFiles' => [],
            'cssFiles' => [],
            'jsFiles' => [],
            'totalFiles' => count($files),
            'directories' => []
        ];
        
        foreach ($files as $file) {
            $path = strtolower($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $directory = dirname($file);
            
            // Track directories
            if ($directory !== '.' && !in_array($directory, $structure['directories'])) {
                $structure['directories'][] = $directory;
            }
            
            // Check for key files and patterns
            if ($file === 'composer.json') $structure['hasComposer'] = true;
            if (str_starts_with($path, 'public/')) $structure['hasPublic'] = true;
            if (str_starts_with($path, 'src/')) $structure['hasSrc'] = true;
            if (str_contains($path, 'controller')) $structure['hasControllers'] = true;
            if (str_contains($path, 'model')) $structure['hasModels'] = true;
            if (str_contains($path, 'view') || str_contains($path, 'template')) $structure['hasViews'] = true;
            if (str_starts_with($path, 'config/')) $structure['hasConfig'] = true;
            if (str_contains($path, 'database') || str_contains($path, 'db')) $structure['hasDatabase'] = true;
            
            // Categorize files
            switch ($extension) {
                case 'php':
                    $structure['phpFiles'][] = $file;
                    break;
                case 'html':
                    $structure['htmlFiles'][] = $file;
                    break;
                case 'css':
                    $structure['cssFiles'][] = $file;
                    break;
                case 'js':
                    $structure['jsFiles'][] = $file;
                    break;
            }
        }
        
        return $structure;
    }

    /**
     * Generate contextual suggestions based on project state
     */
    private function generateContextualSuggestions(array $structure, string $message): array
    {
        $suggestions = [];
        
        // Analyze what's missing/needed
        if (empty($structure['phpFiles'])) {
            $suggestions[] = "New PHP project detected - ready to create structure";
        }
        
        if (!$structure['hasComposer'] && !empty($structure['phpFiles'])) {
            $suggestions[] = "Missing composer.json - may need dependency management";
        }
        
        if (!$structure['hasPublic'] && count($structure['phpFiles']) > 1) {
            $suggestions[] = "No public directory - may need web entry point";
        }
        
        if (count($structure['phpFiles']) > 3 && !$structure['hasControllers']) {
            $suggestions[] = "Multiple PHP files - could benefit from MVC structure";
        }
        
        return $suggestions;
    }

    /**
     * Fallback directory scan if Finder fails
     */
    private function basicDirectoryScan(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($this->projectRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                
                // Skip vendor, .git, etc.
                if (!str_starts_with($relativePath, 'vendor/') && 
                    !str_starts_with($relativePath, '.git/') &&
                    !str_starts_with($relativePath, 'node_modules/')) {
                    $files[] = $relativePath;
                }
            }
        }
        
        return $files;
    }

    /**
     * Legacy method for fallback - now just delegates to ChatService
     */
    public function createProjectStructure(string $message): array
    {
        return [
            'content' => "Project structure creation should be handled by AI endpoint for better results.",
            'files' => []
        ];
    }
}