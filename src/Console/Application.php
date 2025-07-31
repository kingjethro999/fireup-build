<?php

namespace FireUp\PhpBuild\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FireUp\PhpBuild\Console\Commands\BuildCommand;
use FireUp\PhpBuild\Console\Commands\ServeCommand;
use FireUp\PhpBuild\Console\Commands\CreateCommand;
use FireUp\PhpBuild\Console\Commands\ChatCommand;
use FireUp\PhpBuild\Console\Commands\DebugCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PHP Build', '1.0.0');
        $this->setCatchExceptions(true);
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        
        $commands[] = new BuildCommand();
        $commands[] = new ServeCommand();
        $commands[] = new CreateCommand();
        $commands[] = new ChatCommand();
        $commands[] = new DebugCommand();
        
        return $commands;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        if (!$input) {
            $input = new \Symfony\Component\Console\Input\ArgvInput();
        }
        
        if (!$output) {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        }

        return parent::run($input, $output);
    }
} 