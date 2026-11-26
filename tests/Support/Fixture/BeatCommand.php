<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test application only: a scheduled task declared under scheduled_tasks.heartbeats, which succeeds or fails on demand.
 */
#[AsCommand(name: self::NAME, description: 'Test command of the heartbeat (US-296).')]
final class BeatCommand extends Command
{
    public const NAME = 'tmonier:platform-health:test-beat';

    protected function configure(): void
    {
        $this->addOption('fail', null, InputOption::VALUE_NONE, 'Exit with code 1.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return true === $input->getOption('fail') ? Command::FAILURE : Command::SUCCESS;
    }
}
