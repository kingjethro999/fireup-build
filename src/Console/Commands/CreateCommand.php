<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use FireUp\PhpBuild\Services\CodeGenerator;

class CreateCommand extends Command
{
    protected static $defaultName = 'create';
    protected static $defaultDescription = 'Create new project or component';

    private CodeGenerator $codeGenerator;

    public function __construct()
    {
        parent::__construct();
        $this->codeGenerator = new CodeGenerator();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Type to create (project, controller, model, class)')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the item to create')
            ->addOption('template', 't', InputOption::VALUE_REQUIRED, 'Template to use', 'default')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite existing files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        $template = $input->getOption('template');
        $force = $input->getOption('force');

        $io->title('✨ PHP Build - Create Command');

        try {
            switch ($type) {
                case 'project':
                    return $this->createProject($io, $name, $template, $force);
                case 'controller':
                    return $this->createController($io, $name, $template, $force);
                case 'model':
                    return $this->createModel($io, $name, $template, $force);
                case 'class':
                    return $this->createClass($io, $name, $template, $force);
                default:
                    $io->error("Unknown type: {$type}");
                    $io->text('Available types: project, controller, model, class');
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error("Creation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function createProject(SymfonyStyle $io, string $name, string $template, bool $force): int
    {
        $io->text("Creating new project: {$name}");
        
        $message = "create a new PHP project called {$name}";
        $result = $this->codeGenerator->createProjectStructure($message);
        
        if (isset($result['files']) && !empty($result['files'])) {
            $io->success("Project '{$name}' created successfully!");
            $io->section('Created Files:');
            foreach ($result['files'] as $file) {
                $io->text("📄 {$file['path']}");
            }
            
            $io->newLine();
            $io->text('Next steps:');
            $io->text('1. cd ' . $name);
            $io->text('2. composer install');
            $io->text('3. php bin/php-build serve');
        } else {
            $io->error($result['content']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createController(SymfonyStyle $io, string $name, string $template, bool $force): int
    {
        $io->text("Creating controller: {$name}");
        
        $message = "create a controller called {$name}";
        $result = $this->codeGenerator->generateFromRequest($message);
        
        if (isset($result['files']) && !empty($result['files'])) {
            $io->success("Controller '{$name}' created successfully!");
            foreach ($result['files'] as $file) {
                $io->text("📄 {$file['path']}");
            }
        } else {
            $io->error($result['content']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createModel(SymfonyStyle $io, string $name, string $template, bool $force): int
    {
        $io->text("Creating model: {$name}");
        
        $message = "create a model called {$name}";
        $result = $this->codeGenerator->generateFromRequest($message);
        
        if (isset($result['files']) && !empty($result['files'])) {
            $io->success("Model '{$name}' created successfully!");
            foreach ($result['files'] as $file) {
                $io->text("📄 {$file['path']}");
            }
        } else {
            $io->error($result['content']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createClass(SymfonyStyle $io, string $name, string $template, bool $force): int
    {
        $io->text("Creating class: {$name}");
        
        $message = "create a class called {$name}";
        $result = $this->codeGenerator->generateFromRequest($message);
        
        if (isset($result['files']) && !empty($result['files'])) {
            $io->success("Class '{$name}' created successfully!");
            foreach ($result['files'] as $file) {
                $io->text("📄 {$file['path']}");
            }
        } else {
            $io->error($result['content']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 