<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use FireUp\PhpBuild\Services\DebugService;

class DebugCommand extends Command
{
    protected static $defaultName = 'debug';
    protected static $defaultDescription = 'Debug PHP application';

    private DebugService $debugService;

    public function __construct()
    {
        parent::__construct();
        $this->debugService = new DebugService();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'File to debug')
            ->addOption('fix', 'f', InputOption::VALUE_NONE, 'Automatically fix issues')
            ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $fix = $input->getOption('fix');
        $verbose = $input->getOption('verbose');

        $io->title('🐛 PHP Build - Debug Command');

        try {
            if ($file) {
                return $this->debugFile($io, $file, $fix, $verbose);
            } else {
                return $this->debugProject($io, $fix, $verbose);
            }
        } catch (\Exception $e) {
            $io->error("Debug failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function debugFile(SymfonyStyle $io, string $file, bool $fix, bool $verbose): int
    {
        $io->text("Debugging file: {$file}");

        // Validate PHP syntax
        $validation = $this->debugService->validatePhpSyntax($file);
        
        if ($validation['valid']) {
            $io->success("✅ {$file} has valid PHP syntax");
        } else {
            $io->error("❌ {$file} has syntax errors:");
            $io->text($validation['message']);
            
            if ($fix) {
                $io->text('🔧 Attempting to fix syntax errors...');
                $result = $this->debugService->analyzeAndFix("fix syntax errors in {$file}");
                $io->text($result['analysis']);
                
                if (!empty($result['suggestions'])) {
                    $io->section('Suggestions:');
                    foreach ($result['suggestions'] as $suggestion) {
                        $io->text("💡 {$suggestion}");
                    }
                }
            }
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function debugProject(SymfonyStyle $io, bool $fix, bool $verbose): int
    {
        $io->text('🔍 Analyzing entire project...');

        $phpFiles = $this->findPhpFiles();
        $errors = [];
        $warnings = [];

        foreach ($phpFiles as $file) {
            $validation = $this->debugService->validatePhpSyntax($file);
            
            if (!$validation['valid']) {
                $errors[] = "{$file}: {$validation['message']}";
            } elseif ($verbose) {
                $io->text("✅ {$file}");
            }
        }

        if (empty($errors)) {
            $io->success('🎉 All PHP files have valid syntax!');
            
            if ($verbose) {
                $io->section('Project Analysis:');
                $io->text("📊 Total PHP files: " . count($phpFiles));
                $io->text("✅ All files valid");
            }
        } else {
            $io->error('❌ Found syntax errors:');
            foreach ($errors as $error) {
                $io->text($error);
            }
            
            if ($fix) {
                $io->text('🔧 Attempting to fix errors...');
                foreach ($errors as $error) {
                    $file = explode(':', $error)[0];
                    $result = $this->debugService->analyzeAndFix("fix errors in {$file}");
                    $io->text($result['analysis']);
                }
            }
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
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
} 