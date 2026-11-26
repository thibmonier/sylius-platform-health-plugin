<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\BuildsHealthCommand;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ReadsJsonOutput;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * One test per line of the table of the exit codes of US-307, then the shape of the outputs.
 */
final class HealthCheckCommandTest extends TestCase
{
    use BuildsHealthCommand;
    use ReadsJsonOutput;

    public function testEveryCheckOkExitsWithZero(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Ok),
        );

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('environment.php', $tester->getDisplay());
        self::assertStringContainsString('data.connection', $tester->getDisplay());
    }

    public function testAWarningWithoutCriticalExitsWithOne(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning),
        );

        self::assertSame(1, $tester->execute([]));
    }

    public function testAnUnknownWithoutCriticalExitsWithOne(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('plugins.demo', HealthCategory::Plugins, HealthStatus::Unknown),
        );

        self::assertSame(1, $tester->execute([]));
    }

    public function testACriticalAmongOtherStatusesExitsWithTwo(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('plugins.demo', HealthCategory::Plugins, HealthStatus::Unknown),
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical),
        );

        self::assertSame(2, $tester->execute([]));
    }

    public function testASingleCriticalCheckExitsWithTwo(): void
    {
        $tester = $this->tester($this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical));

        self::assertSame(2, $tester->execute([]));
    }

    public function testAnUnknownCategoryExitsWithThreeAndNamesTheRefusedValue(): void
    {
        $tester = $this->tester($this->check('environment.php', HealthCategory::Environment, HealthStatus::Critical));

        self::assertSame(3, $tester->execute(['--category' => ['inconnue']]));
        self::assertStringContainsString('inconnue', $tester->getDisplay());
    }

    public function testAnOnlyThatDesignatesNoCheckExitsWithThreeAndNamesTheRefusedValue(): void
    {
        $tester = $this->tester($this->check('data.connection', HealthCategory::Data, HealthStatus::Critical));

        self::assertSame(3, $tester->execute(['--only' => ['data.inconnue']]));
        self::assertStringContainsString('data.inconnue', $tester->getDisplay());
    }

    public function testAnUnknownFormatExitsWithThreeAndNamesTheRefusedValue(): void
    {
        $tester = $this->tester($this->check('data.connection', HealthCategory::Data, HealthStatus::Critical));

        self::assertSame(3, $tester->execute(['--format' => 'xml']));
        self::assertStringContainsString('xml', $tester->getDisplay());
    }

    public function testAKnownCategoryWithoutCheckExitsWithZeroAndSaysThereIsNoCheck(): void
    {
        $tester = $this->tester($this->check('environment.php', HealthCategory::Environment, HealthStatus::Critical));

        self::assertSame(0, $tester->execute(['--category' => ['shop']]));
        self::assertStringContainsString('aucune sonde', mb_strtolower($tester->getDisplay()));
    }

    public function testAnInvalidOptionRunsNoCheck(): void
    {
        $executions = 0;
        $counting = new FakeCheck('data.connection', HealthCategory::Data, static function () use (&$executions): HealthResult {
            ++$executions;

            return HealthResult::ok(new TranslatableMessage('Fine'));
        });
        $tester = $this->tester($counting);

        $tester->execute(['--format' => 'xml']);

        self::assertSame(0, $executions);
    }

    public function testTheTextOutputSpellsTheStatusOutAndGroupsTheChecksByCategory(): void
    {
        $tester = $this->tester(
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning, 'Slow'),
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok, 'Supported'),
            $this->check('data.migrations', HealthCategory::Data, HealthStatus::Critical, 'Pending'),
        );

        $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertMatchesRegularExpression('/Environnement\R.*environment\.php.*Supported/', $display);
        self::assertMatchesRegularExpression('/Données\R.*data\.connection.*Slow\R.*data\.migrations.*Pending/', $display);
        self::assertStringContainsString('Avertissement', $display);
        self::assertStringContainsString('Critique', $display);
        self::assertStringContainsString('OK', $display);
    }

    public function testTheTextOutputEndsWithASummaryLineNamingTheOverallStatus(): void
    {
        $tester = $this->tester(
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical),
        );

        $tester->execute([]);
        $lines = preg_split('/\R/', trim($tester->getDisplay()));

        self::assertIsArray($lines);
        self::assertStringContainsString('Critique', (string) end($lines));
        self::assertStringContainsString('2', (string) end($lines));
    }

    public function testTheJsonOutputIsValidWithTheDocumentedShapeAndNothingElse(): void
    {
        $tester = $this->tester(
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning, 'Slow'),
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok, 'Supported'),
        );

        $exitCode = $tester->execute(['--format' => 'json']);
        $document = self::document($tester->getDisplay());

        self::assertSame(1, $exitCode);
        self::assertSame(['status', 'generated_at', 'checks'], array_keys($document));
        self::assertSame('warning', $document['status']);
        self::assertSame('2026-11-26T10:00:00+00:00', $document['generated_at']);
        self::assertSame(
            [
                ['identifier' => 'data.connection', 'category' => 'data', 'status' => 'warning', 'summary' => 'Slow'],
                ['identifier' => 'environment.php', 'category' => 'environment', 'status' => 'ok', 'summary' => 'Supported'],
            ],
            self::checks($tester->getDisplay()),
        );
    }

    public function testTheJsonOutputOfAnEmptySelectionIsStillAValidDocument(): void
    {
        $tester = $this->tester($this->check('environment.php', HealthCategory::Environment, HealthStatus::Critical));

        $exitCode = $tester->execute(['--format' => 'json', '--category' => ['shop']]);

        self::assertSame(0, $exitCode);
        self::assertSame([], self::checks($tester->getDisplay()));
    }

    public function testCategoryIsRepeatable(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Ok),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Ok),
        );

        $tester->execute(['--format' => 'json', '--category' => ['data', 'shop']]);

        self::assertSame(['data.connection', 'shop.channels'], self::identifiers($tester->getDisplay()));
    }

    public function testOnlyIsRepeatable(): void
    {
        $tester = $this->tester(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Ok),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Ok),
        );

        $tester->execute(['--format' => 'json', '--only' => ['environment.php', 'shop.channels']]);

        self::assertSame(['environment.php', 'shop.channels'], self::identifiers($tester->getDisplay()));
    }

    public function testTheExitCodeIsComputedOnTheRetainedChecksOnly(): void
    {
        $tester = $this->tester(
            $this->check('data.connection', HealthCategory::Data, HealthStatus::Warning),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical),
        );

        self::assertSame(1, $tester->execute(['--only' => ['data.connection']]));
    }

    public function testACheckThatThrowsIsUnknownWithoutTraceAndItsCauseIsLogged(): void
    {
        $tester = $this->tester(new FakeCheck('data.connection', HealthCategory::Data, static function (): HealthResult {
            throw new \RuntimeException('SQLSTATE[HY000] mysql://root:hunter2@db.internal:3306/shop');
        }));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Non mesurable', $display);
        self::assertStringNotContainsString('hunter2', $display);
        self::assertStringNotContainsString('RuntimeException', $display);
        self::assertDoesNotMatchRegularExpression('/#\d+ /', $display);
        self::assertCount(1, $this->logger->loggedExceptions());
        self::assertStringContainsString('hunter2', $this->logger->loggedExceptions()[0]->getMessage());
    }

    public function testASecretInASummaryIsMaskedInTheTextAndInTheJson(): void
    {
        $leaky = $this->check('data.connection', HealthCategory::Data, HealthStatus::Critical, 'Cannot connect to mysql://shop:s3cret@db.internal/sylius, password=hunter2');

        foreach ([[], ['--format' => 'json']] as $options) {
            $tester = $this->tester($leaky);
            $tester->execute($options);

            self::assertStringNotContainsString('s3cret', $tester->getDisplay());
            self::assertStringNotContainsString('hunter2', $tester->getDisplay());
            self::assertStringContainsString('mysql://***@db.internal/sylius', $tester->getDisplay());
        }
    }

    public function testAnExecutionTraceInASummaryIsReplacedByAGenericMessage(): void
    {
        $tester = $this->tester($this->check('data.connection', HealthCategory::Data, HealthStatus::Warning, "Stack trace:\n#0 /var/www/shop/src/Foo.php(12): Foo->bar()\n#1 {main}"));

        $tester->execute([]);

        self::assertStringNotContainsString('Foo.php', $tester->getDisplay());
        self::assertStringNotContainsString('Stack trace', $tester->getDisplay());
    }

    public function testTheDetailAndTheActionLinkOfAResultAreNotPrinted(): void
    {
        $tester = $this->tester(new FakeCheck('data.connection', HealthCategory::Data, static fn (): HealthResult => HealthResult::warning(
            new TranslatableMessage('Slow'),
            new TranslatableMessage('Long detail of the probe'),
        )));

        $tester->execute(['--format' => 'json']);

        self::assertStringContainsString('Slow', $tester->getDisplay());
        self::assertStringNotContainsString('Long detail', $tester->getDisplay());
    }
}
