<?php

namespace FireUp\PhpBuild\Stubs;

// Stub classes for Symfony components when not available
if (!class_exists('Symfony\Component\Filesystem\Filesystem')) {
    class Filesystem
    {
        public function exists(string $path): bool
        {
            return file_exists($path);
        }
        
        public function mkdir(string $path): void
        {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        public function dumpFile(string $path, string $content): void
        {
            $this->mkdir(dirname($path));
            file_put_contents($path, $content);
        }
        
        public function remove(string $path): void
        {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        public function readFile(string $path): string
        {
            return file_get_contents($path) ?: '';
        }
    }
    
    class_alias('FireUp\PhpBuild\Stubs\Filesystem', 'Symfony\Component\Filesystem\Filesystem');
}

if (!class_exists('Symfony\Component\Finder\Finder')) {
    class Finder
    {
        private array $paths = [];
        private array $names = [];
        
        public function files(): self
        {
            return $this;
        }
        
        public function name(string $pattern): self
        {
            $this->names[] = $pattern;
            return $this;
        }
        
        public function in(string $path): self
        {
            $this->paths[] = $path;
            return $this;
        }
        
        public function getIterator(): \Iterator
        {
            $files = [];
            foreach ($this->paths as $path) {
                if (is_dir($path)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $files[] = $file;
                        }
                    }
                }
            }
            return new \ArrayIterator($files);
        }
    }
    
    class_alias('FireUp\PhpBuild\Stubs\Finder', 'Symfony\Component\Finder\Finder');
}

if (!class_exists('Symfony\Component\Process\Process')) {
    class Process
    {
        private array $command;
        private string $output = '';
        private string $errorOutput = '';
        private int $exitCode = 0;
        
        public function __construct(array $command)
        {
            $this->command = $command;
        }
        
        public function run(): void
        {
            $command = implode(' ', $this->command);
            $this->output = shell_exec($command . ' 2>&1') ?: '';
            $this->exitCode = 0; // Simplified
        }
        
        public function isSuccessful(): bool
        {
            return $this->exitCode === 0;
        }
        
        public function getOutput(): string
        {
            return $this->output;
        }
        
        public function getErrorOutput(): string
        {
            return $this->errorOutput;
        }
    }
    
    class_alias('FireUp\PhpBuild\Stubs\Process', 'Symfony\Component\Process\Process');
} 