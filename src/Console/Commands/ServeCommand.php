<?php

namespace FireUp\PhpBuild\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';
    protected static $defaultDescription = 'Start development server for user\'s built application (serves index.php)';

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host to bind to', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port to bind to', '8000')
            ->addOption('public', null, InputOption::VALUE_REQUIRED, 'Public directory', 'public');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $public = $input->getOption('public');

        $io->title('🚀 PHP Build - Development Server');
        $io->text("Starting server for user's built application on http://{$host}:{$port}");
        $io->text("This serves the user's index.php and other application files");
        $io->text("Document root: {$public}");
        $io->text('Press Ctrl+C to stop the server');
        $io->newLine();

        try {
            $command = "php -S {$host}:{$port} -t {$public}";
            $io->text("Running: {$command}");
            $io->newLine();

            passthru($command);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to start server: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
} 