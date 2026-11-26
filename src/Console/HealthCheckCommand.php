<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Console;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;

/**
 * The health diagnostic from a shell. It runs the checks through the runner (same time budget, same masking as the page),
 * never through the shared cache of the page: a diagnostic in a console must be fresh. It writes nothing.
 */
#[AsCommand(name: 'tmonier:health:check', description: 'Runs the platform health checks and exits with 0, 1 or 2 according to the worst status')]
final class HealthCheckCommand extends Command
{
    private const FORMATS = ['text', 'json'];

    private readonly TextReport $textReport;

    private readonly JsonReport $jsonReport;

    public function __construct(
        private readonly HealthCheckRunner $runner,
        private readonly TranslatorInterface $translator,
        SafeText $safeText,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();

        $this->textReport = new TextReport($translator, $safeText);
        $this->jsonReport = new JsonReport($safeText);
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
        $format = $this->scalarOption($input, 'format');
        $categories = $this->listOption($input, 'category');
        $only = $this->listOption($input, 'only');

        $refused = $this->refusedChoice('--format', [$format], self::FORMATS) ?? $this->refusedChoice('--category', $categories, array_column(HealthCategory::cases(), 'value'));
        if (null !== $refused) {
            return $this->refuse($output, $refused);
        }

        $reports = $this->runner->run();
        $unknownOnly = array_diff($only, array_map(static fn (HealthCheckReport $report): string => $report->identifier, $reports));
        if ([] !== $unknownOnly) {
            return $this->refuse($output, $this->translator->trans('tmonier_platform_health.console.invalid_only', ['%value%' => implode(', ', $unknownOnly)]));
        }

        return $this->report($output, $format, $this->select($reports, $categories, $only));
    }

    /**
     * @param list<HealthCheckReport> $reports
     * @param list<string>            $categories
     * @param list<string>            $only
     *
     * @return list<HealthCheckReport>
     */
    private function select(array $reports, array $categories, array $only): array
    {
        $retained = array_filter($reports, static fn (HealthCheckReport $report): bool => [] === $categories || \in_array($report->category->value, $categories, true));

        return array_values(array_filter($retained, static fn (HealthCheckReport $report): bool => [] === $only || \in_array($report->identifier, $only, true)));
    }

    /**
     * @param list<HealthCheckReport> $reports
     */
    private function report(OutputInterface $output, string $format, array $reports): int
    {
        $lines = 'json' === $format ? [$this->jsonReport->render($reports, $this->clock->now())] : $this->textReport->lines($reports);
        foreach ($lines as $line) {
            $output->writeln($line, OutputInterface::OUTPUT_RAW);
        }

        return (new Verdict($reports))->exitCode();
    }

    /**
     * @param list<string> $values
     * @param list<string> $allowed
     */
    private function refusedChoice(string $option, array $values, array $allowed): ?string
    {
        foreach ($values as $value) {
            if (!\in_array($value, $allowed, true)) {
                return $this->translator->trans('tmonier_platform_health.console.invalid_choice', ['%option%' => $option, '%value%' => $value, '%allowed%' => implode(', ', $allowed)]);
            }
        }

        return null;
    }

    private function refuse(OutputInterface $output, string $message): int
    {
        $stream = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $stream->writeln($message, OutputInterface::OUTPUT_RAW);

        return Verdict::EXIT_INVALID_OPTION;
    }

    private function scalarOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);

        return \is_string($value) ? $value : '';
    }

    /**
     * @return list<string>
     */
    private function listOption(InputInterface $input, string $name): array
    {
        $values = $input->getOption($name);

        return \is_array($values) ? array_values(array_filter($values, \is_string(...))) : [];
    }
}
