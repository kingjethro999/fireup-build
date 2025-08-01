<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use FireUp\PhpBuild\Services\ChatService;
use FireUp\PhpBuild\Web\Server;

class ChatCommand extends Command
{
    protected static $defaultName = 'chat';
    protected static $defaultDescription = 'Start interactive chat with AI assistant for PHP development';

    protected function configure(): void
    {
        $this
            ->addArgument('message', InputArgument::OPTIONAL, 'Message to send to AI assistant')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Start interactive chat mode')
            ->addOption('web', 'w', InputOption::VALUE_NONE, 'Start web interface')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port for web interface', 8000)
            ->setHelp('
This command provides an AI-powered chat interface for PHP development.

Examples:
  <info>php artisan chat "create a blog"</info>     - Send a single message
  <info>php artisan chat --interactive</info>       - Start interactive chat mode
  <info>php artisan chat --web</info>               - Start web interface
  <info>php artisan chat --web --port=3000</info>   - Start web interface on port 3000

The AI assistant can help you:
• Create new PHP projects and applications
• Generate classes, controllers, models, and views
• Debug and fix code issues
• Edit and manage files
• Build complete applications from scratch
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');
        $interactive = $input->getOption('interactive');
        $web = $input->getOption('web');
        $port = (int) $input->getOption('port');

        // Show header
        $io->title('🔥 FireUp PHP Build - AI Development Assistant');
        $io->text('Intelligent PHP development powered by AI');

        if ($web) {
            return $this->startWebInterface($io, $port);
        }

        if ($interactive) {
            return $this->startInteractiveMode($io);
        }

        if ($message) {
            return $this->processSingleMessage($io, $message);
        }

        // Default: show help and start interactive mode
        $io->note('No message provided. Starting interactive mode...');
        return $this->startInteractiveMode($io);
    }

    private function startWebInterface(SymfonyStyle $io, int $port): int
    {
        $io->section('🌐 Starting Web Interface');
        $io->text([
            'Starting web server on port ' . $port,
            'The AI assistant will create and edit files in your project.',
            'Make sure you\'re in the correct project directory!'
        ]);

        $io->success([
            'Web interface starting...',
            'Open your browser to: <info>http://localhost:' . $port . '/.chat/interface.html</info>',
            'Press Ctrl+C to stop the server'
        ]);

        try {
            $server = new Server();
            $server->start($port);
        } catch (\Exception $e) {
            $io->error('Failed to start web server: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function startInteractiveMode(SymfonyStyle $io): int
    {
        $io->section('💬 Interactive Chat Mode');
        $io->text([
            'You can now chat with the AI assistant.',
            'The AI will analyze your project and help you with PHP development.',
            'Type "exit" or "quit" to end the session.',
            ''
        ]);

        $chatService = new ChatService();

        while (true) {
            $message = $io->ask('You', null, function ($input) {
                if (empty($input)) {
                    throw new \Exception('Message cannot be empty');
                }
                return $input;
            });

            if (in_array(strtolower($message), ['exit', 'quit', 'bye'])) {
                $io->success('Goodbye! Happy coding! 🚀');
                break;
            }

            try {
                $io->text('🤖 AI Assistant is thinking...');
                $response = $chatService->processMessage($message);
                
                $this->displayResponse($io, $response);
                
            } catch (\Exception $e) {
                $io->error('Error: ' . $e->getMessage());
            }

            $io->newLine();
        }

        return Command::SUCCESS;
    }

    private function processSingleMessage(SymfonyStyle $io, string $message): int
    {
        $io->section('💬 Processing Message');
        $io->text('Message: ' . $message);
        $io->newLine();

        try {
            $chatService = new ChatService();
            $response = $chatService->processMessage($message);
            
            $this->displayResponse($io, $response);
            
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayResponse(SymfonyStyle $io, array $response): void
    {
        $type = $response['type'] ?? 'response';
        $content = $response['content'] ?? 'No response received';

        switch ($type) {
            case 'project_creation':
                $io->success('✅ Project Creation');
                $io->text($content);
                if (!empty($response['files'])) {
                    $io->listing($response['files']);
                }
                break;

            case 'file_operation':
                $io->info('📝 File Operation');
                $io->text($content);
                if (!empty($response['files'])) {
                    $io->listing($response['files']);
                }
                break;

            case 'debug':
                $io->warning('🐛 Debug');
                $io->text($content);
                break;

            case 'code_generation':
                $io->success('💻 Code Generation');
                $io->text($content);
                if (!empty($response['files'])) {
                    $io->listing($response['files']);
                }
                break;

            case 'error':
                $io->error('❌ Error');
                $io->text($content);
                break;

            default:
                $io->text('🤖 AI Response');
                $io->text($content);
                break;
        }
    }
} 