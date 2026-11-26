<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Translation\Translator;
use Tmonier\SyliusPlatformHealthPlugin\Console\HealthCheckCommand;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Security\SafeText;
use Tmonier\SyliusPlatformHealthPlugin\Security\SensitiveValueMasker;

/**
 * The console command on probes handed over by the test, a real runner, the French catalogue of the plugin and a fixed clock:
 * no kernel, no database, no cache.
 */
trait BuildsHealthCommand
{
    private RecordingLogger $logger;

    protected function tester(HealthCheckInterface ...$checks): CommandTester
    {
        $this->logger = new RecordingLogger();
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', TranslationCatalogue::fromFile('fr'), 'fr');
        $safeText = new SafeText($translator, new SensitiveValueMasker('/var/www/shop'), $this->logger);
        $runner = new HealthCheckRunner($checks, $this->logger);

        return new CommandTester(new HealthCheckCommand($runner, $translator, $safeText, new MockClock('2026-11-26 10:00:00', 'UTC')));
    }

    protected function check(string $identifier, HealthCategory $category, HealthStatus $status, string $summary = 'Summary'): HealthCheckInterface
    {
        return new FakeCheck($identifier, $category, static fn (): HealthResult => new HealthResult($status, new TranslatableMessage($summary)));
    }
}
