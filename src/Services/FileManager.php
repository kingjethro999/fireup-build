<?php

namespace FireUp\PhpBuild\Services;

use Exception;

class FileManager
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    /**
     * Create a new file with content
     */
    public function createFile(string $path, string $content): bool
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            $directory = dirname($fullPath);
            
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Write file
            return file_put_contents($fullPath, $content) !== false;
            
        } catch (Exception $e) {
            error_log("File creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing file
     */
    public function updateFile(string $path, string $content): bool
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            return file_put_contents($fullPath, $content) !== false;
            
        } catch (Exception $e) {
            error_log("File update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $path): bool
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            return unlink($fullPath);
            
        } catch (Exception $e) {
            error_log("File deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read file content
     */
    public function readFile(string $path): ?string
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            return file_get_contents($fullPath);
            
        } catch (Exception $e) {
            error_log("File read error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $path): bool
    {
        return file_exists($this->projectRoot . '/' . $path);
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            $stat = stat($fullPath);
            
            return [
                'size' => $stat['size'],
                'modified' => $stat['mtime'],
                'permissions' => $stat['mode'],
                'extension' => pathinfo($path, PATHINFO_EXTENSION),
                'basename' => basename($path),
                'dirname' => dirname($path)
            ];
            
        } catch (Exception $e) {
            error_log("File info error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * List files in a directory
     */
    public function listFiles(string $directory = ''): array
    {
        try {
            $fullPath = $this->projectRoot . '/' . $directory;
            
            if (!is_dir($fullPath)) {
                return [];
            }
            
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($fullPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                    
                    // Skip vendor and other system directories
                    if (!str_starts_with($relativePath, 'vendor/') && 
                        !str_starts_with($relativePath, '.git/') &&
                        !str_starts_with($relativePath, 'node_modules/')) {
                        $files[] = $relativePath;
                    }
                }
            }
            
            return $files;
            
        } catch (Exception $e) {
            error_log("File listing error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a directory
     */
    public function createDirectory(string $path): bool
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (is_dir($fullPath)) {
                return true;
            }
            
            return mkdir($fullPath, 0755, true);
            
        } catch (Exception $e) {
            error_log("Directory creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a directory and its contents
     */
    public function deleteDirectory(string $path): bool
    {
        try {
            $fullPath = $this->projectRoot . '/' . $path;
            
            if (!is_dir($fullPath)) {
                return false;
            }
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            
            return rmdir($fullPath);
            
        } catch (Exception $e) {
            error_log("Directory deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Copy a file
     */
    public function copyFile(string $source, string $destination): bool
    {
        try {
            $sourcePath = $this->projectRoot . '/' . $source;
            $destPath = $this->projectRoot . '/' . $destination;
            
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            $directory = dirname($destPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            return copy($sourcePath, $destPath);
            
        } catch (Exception $e) {
            error_log("File copy error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Move a file
     */
    public function moveFile(string $source, string $destination): bool
    {
        try {
            $sourcePath = $this->projectRoot . '/' . $source;
            $destPath = $this->projectRoot . '/' . $destination;
            
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            $directory = dirname($destPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            return rename($sourcePath, $destPath);
            
        } catch (Exception $e) {
            error_log("File move error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get project root path
     */
    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * Set project root path
     */
    public function setProjectRoot(string $path): void
    {
        $this->projectRoot = $path;
    }
} 