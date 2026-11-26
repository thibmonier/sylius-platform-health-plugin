<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftReaderInterface;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class SchemaDriftCheckTest extends TestCase
{
    use ResultAssertions;

    public function testASchemaInLineWithTheMappingIsOk(): void
    {
        self::assertSummary($this->execute(0), HealthStatus::Ok, 'tmonier_platform_health.data.schema_drift.ok');
    }

    public function testADriftWarnsWithTheNumberOfStatementsAndNeverTheirSql(): void
    {
        $result = $this->execute(3);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.schema_drift.drift', ['%count%' => 3]);
        self::assertEquals(DocumentationLink::create(), $result->action);
        self::assertStringNotContainsStringIgnoringCase('ALTER TABLE', self::serialized($result));
    }

    public function testWithoutAnOrmTheDriftIsUnknownNeverOk(): void
    {
        self::assertSummary($this->execute(null), HealthStatus::Unknown, 'tmonier_platform_health.data.schema_drift.not_available');
    }

    private function execute(?int $statements): HealthResult
    {
        return (new SchemaDriftCheck(new class($statements) implements SchemaDriftReaderInterface {
            public function __construct(private readonly ?int $statements)
            {
            }

            public function countStatements(): ?int
            {
                return $this->statements;
            }
        }))->run();
    }
}
