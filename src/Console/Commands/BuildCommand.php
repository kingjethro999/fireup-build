<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use FireUp\PhpBuild\Services\CodeGenerator;

class BuildCommand extends Command
{
    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build and compile PHP application';

    private CodeGenerator $codeGenerator;

    public function __construct()
    {
        parent::__construct();
        $this->codeGenerator = new CodeGenerator();
    }

    protected function configure(): void
    {
        $this
            ->addOption('optimize', 'o', InputOption::VALUE_NONE, 'Optimize the build')
            ->addOption('production', 'p', InputOption::VALUE_NONE, 'Build for production')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch for changes and rebuild');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $optimize = $input->getOption('optimize');
        $production = $input->getOption('production');
        $watch = $input->getOption('watch');

        $io->title('🏗️  PHP Build - Building Application');

        try {
            if ($watch) {
                $this->watchAndBuild($io);
                return Command::SUCCESS;
            }

            $this->buildApplication($io, $optimize, $production);
            $io->success('Build completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Build failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function buildApplication(SymfonyStyle $io, bool $optimize, bool $production): void
    {
        $io->text('📦 Building application...');

        // Validate PHP syntax
        $io->text('🔍 Validating PHP syntax...');
        $this->validateSyntax($io);

        // Generate autoloader
        $io->text('📚 Generating autoloader...');
        $this->generateAutoloader($io);

        // Optimize if requested
        if ($optimize) {
            $io->text('⚡ Optimizing build...');
            $this->optimizeBuild($io);
        }

        // Production optimizations
        if ($production) {
            $io->text('🚀 Applying production optimizations...');
            $this->applyProductionOptimizations($io);
        }

        $io->text('✅ Build process completed');
    }

    private function watchAndBuild(SymfonyStyle $io): void
    {
        $io->text('👀 Watching for changes...');
        $io->text('Press Ctrl+C to stop watching');

        while (true) {
            // Check for file changes
            if ($this->hasFileChanges()) {
                $io->text('🔄 Changes detected, rebuilding...');
                $this->buildApplication($io, false, false);
                $io->text('✅ Rebuild completed');
            }

            sleep(2); // Check every 2 seconds
        }
    }

    private function validateSyntax(SymfonyStyle $io): void
    {
        $phpFiles = $this->findPhpFiles();
        $errors = [];

        foreach ($phpFiles as $file) {
            $output = [];
            $returnCode = 0;
            exec("php -l {$file} 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $errors[] = "{$file}: " . implode("\n", $output);
            }
        }

        if (!empty($errors)) {
            throw new \Exception("Syntax errors found:\n" . implode("\n", $errors));
        }
    }

    private function generateAutoloader(SymfonyStyle $io): void
    {
        if (file_exists('composer.json')) {
            exec('composer dump-autoload --optimize', $output, $returnCode);
            if ($returnCode !== 0) {
                throw new \Exception('Failed to generate autoloader');
            }
        }
    }

    private function optimizeBuild(SymfonyStyle $io): void
    {
        // Remove development files
        $devFiles = ['.env.example', 'README.md', 'tests/'];
        foreach ($devFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Minify CSS and JS if they exist
        $this->minifyAssets($io);
    }

    private function applyProductionOptimizations(SymfonyStyle $io): void
    {
        // Set production environment
        if (file_exists('.env')) {
            file_put_contents('.env', str_replace('APP_ENV=development', 'APP_ENV=production', file_get_contents('.env')));
        }

        // Disable error reporting
        $this->disableErrorReporting($io);
    }

    private function findPhpFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator('.', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function hasFileChanges(): bool
    {
        // Simple file change detection
        static $lastModified = 0;
        $currentModified = filemtime('.');
        
        if ($currentModified > $lastModified) {
            $lastModified = $currentModified;
            return true;
        }

        return false;
    }

    private function minifyAssets(SymfonyStyle $io): void
    {
        // Minify CSS files
        $cssFiles = glob('public/css/*.css');
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $minified = preg_replace('/\s+/', ' ', $content);
            $minified = preg_replace('/\/\*.*?\*\//', '', $minified);
            file_put_contents($file, trim($minified));
        }

        // Minify JS files
        $jsFiles = glob('public/js/*.js');
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);
            $minified = preg_replace('/\s+/', ' ', $content);
            $minified = preg_replace('/\/\/.*$/m', '', $minified);
            file_put_contents($file, trim($minified));
        }
    }

    private function disableErrorReporting(SymfonyStyle $io): void
    {
        // Create a production bootstrap file
        $bootstrap = "<?php\n";
        $bootstrap .= "error_reporting(0);\n";
        $bootstrap .= "ini_set('display_errors', 0);\n";
        $bootstrap .= "ini_set('log_errors', 1);\n";
        $bootstrap .= "ini_set('error_log', 'logs/error.log');\n";

        file_put_contents('public/bootstrap.php', $bootstrap);
    }
} 