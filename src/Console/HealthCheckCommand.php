<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Console;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;

#[AsCommand(name: 'tmonier:health:check', description: 'Runs the platform health checks and exits with 0, 1 or 2 according to the worst status')]
final class HealthCheckCommand extends Command
{
    public function __construct(
        private readonly HealthCheckRunner $runner,
        private readonly TranslatorInterface $translator,
        private readonly SafeText $safeText,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    /**
     * Stub of the red commit (US-307): the wiring is final, the behaviour is not written yet.
     *
     * @return list<object>
     */
    public function dependencies(): array
    {
        return [$this->runner, $this->translator, $this->safeText, $this->clock];
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json', 'text')
            ->addOption('category', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only the checks of this category (repeatable)')
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only this check, by identifier (repeatable)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
