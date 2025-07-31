<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use FireUp\PhpBuild\Services\ChatService;
use FireUp\PhpBuild\Services\FileManager;
use FireUp\PhpBuild\Services\CodeGenerator;

class ChatCommand extends Command
{
    protected static $defaultName = 'chat';
    protected static $defaultDescription = 'Interactive chat interface for PHP development';

    private ChatService $chatService;
    private FileManager $fileManager;
    private CodeGenerator $codeGenerator;

    public function __construct()
    {
        parent::__construct();
        $this->chatService = new ChatService();
        $this->fileManager = new FileManager();
        $this->codeGenerator = new CodeGenerator();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('message', InputArgument::OPTIONAL, 'Initial message to send')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Start interactive mode')
            ->addOption('web', 'w', InputOption::VALUE_NONE, 'Start web interface')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port for web interface', 8000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');
        $interactive = $input->getOption('interactive');
        $web = $input->getOption('web');
        $port = $input->getOption('port');

        if ($web) {
            return $this->startWebInterface($io, $port);
        }

        if ($interactive || !$message) {
            return $this->startInteractiveMode($io);
        }

        return $this->processMessage($io, $message);
    }

    private function startWebInterface(SymfonyStyle $io, int $port): int
    {
        $io->info("Starting web chat interface on port {$port}...");
        $io->info("Open your browser to: http://localhost:{$port}/.chat/interface.html");
        $io->info("This opens the AI chat interface for interacting with the assistant");
        $io->info("The AI will create and edit files in your current project directory");
        
        $server = new \FireUp\PhpBuild\Web\Server($port);
        $server->start();
        
        return Command::SUCCESS;
    }

    private function startInteractiveMode(SymfonyStyle $io): int
    {
        $io->title('🤖 PHP Build Interactive Chat');
        $io->text('Type your requests and I\'ll help you build clean PHP applications!');
        $io->text('Type "exit" to quit, "help" for commands.');
        $io->newLine();

        while (true) {
            $message = $io->ask('You');
            
            if (strtolower($message) === 'exit') {
                $io->info('Goodbye! 👋');
                break;
            }

            if (strtolower($message) === 'help') {
                $this->showHelp($io);
                continue;
            }

            $this->processMessage($io, $message);
            $io->newLine();
        }

        return Command::SUCCESS;
    }

    private function processMessage(SymfonyStyle $io, string $message): int
    {
        try {
            $io->text('🤔 Thinking...');
            
            $response = $this->chatService->processMessage($message);
            
            if ($response['type'] === 'code_generation') {
                $this->handleCodeGeneration($io, $response);
            } elseif ($response['type'] === 'file_operation') {
                $this->handleFileOperation($io, $response);
            } elseif ($response['type'] === 'debug') {
                $this->handleDebug($io, $response);
            } else {
                $io->text($response['content']);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function handleCodeGeneration(SymfonyStyle $io, array $response): void
    {
        $io->success('Generated code successfully!');
        $io->text($response['content']);
        
        if (isset($response['files'])) {
            $io->section('Created Files:');
            foreach ($response['files'] as $file) {
                $io->text("📄 {$file['path']}");
            }
        }
    }

    private function handleFileOperation(SymfonyStyle $io, array $response): void
    {
        $io->success($response['message']);
        
        if (isset($response['changes'])) {
            $io->section('Changes Made:');
            foreach ($response['changes'] as $change) {
                $io->text("📝 {$change}");
            }
        }
    }

    private function handleDebug(SymfonyStyle $io, array $response): void
    {
        $io->warning('Debug Information:');
        $io->text($response['content']);
        
        if (isset($response['suggestions'])) {
            $io->section('Suggestions:');
            foreach ($response['suggestions'] as $suggestion) {
                $io->text("💡 {$suggestion}");
            }
        }
    }

    private function showHelp(SymfonyStyle $io): void
    {
        $io->section('Available Commands:');
        $io->text('• Create a new project: "create a new PHP project"');
        $io->text('• Generate code: "create a User class with properties"');
        $io->text('• Debug code: "debug this file" or "fix this error"');
        $io->text('• File operations: "edit this file" or "create a new file"');
        $io->text('• Exit: "exit"');
        $io->newLine();
    }
} 