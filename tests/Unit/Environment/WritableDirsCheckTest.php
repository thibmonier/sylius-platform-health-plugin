<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Environment\WritableDirsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class WritableDirsCheckTest extends TestCase
{
    use ResultAssertions;

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/writable-dirs-' . bin2hex(random_bytes(4));
        (new Filesystem())->mkdir([$this->root . '/var/cache', $this->root . '/var/log', $this->root . '/public']);
    }

    protected function tearDown(): void
    {
        @chmod($this->root . '/var/log', 0o755);
        (new Filesystem())->remove($this->root);
    }

    public function testWritableDirectoriesAreOk(): void
    {
        self::assertSummary($this->execute(), HealthStatus::Ok, 'tmonier_platform_health.environment.writable_dirs.ok');
    }

    public function testAMediaDirectoryNotCreatedYetIsOkWhenItsParentIsWritable(): void
    {
        self::assertDirectoryDoesNotExist($this->root . '/public/media');
        self::assertSame(HealthStatus::Ok, $this->execute()->status);
    }

    public function testANonWritableDirectoryIsCriticalWithItsRelativeNameOnly(): void
    {
        if (0 === posix_geteuid()) {
            self::markTestSkipped('Root writes everywhere: the permission cannot be taken away.');
        }
        chmod($this->root . '/var/log', 0o555);

        $result = $this->execute();

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.environment.writable_dirs.not_writable', ['%directories%' => 'var/log']);
        self::assertEquals(DocumentationLink::create(), $result->action);
        self::assertStringNotContainsString($this->root, self::serialized($result));
    }

    private function execute(): \Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult
    {
        return (new WritableDirsCheck([
            'var/cache' => $this->root . '/var/cache',
            'var/log' => $this->root . '/var/log',
            'public/media' => $this->root . '/public/media',
        ]))->run();
    }
}
