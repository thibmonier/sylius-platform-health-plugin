<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Translation\Translator;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\Security\SensitiveValueMasker;
use Tmonier\SyliusPlatformHealthPlugin\Twig\SafeTextExtension;

final class SafeTextExtensionTest extends TestCase
{
    private const TRACE = "Stack trace:\n#0 /var/www/shop/src/Foo.php(12): Foo->bar()\n#1 {main}";

    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new RecordingLogger();
    }

    public function testTheSecretOfADsnNeverReachesTheScreen(): void
    {
        $text = $this->extension()->text(new TranslatableMessage('Database mysql://shop:s3cret@db.internal/sylius is unreachable'), 'data.demo');

        self::assertStringNotContainsString('s3cret', $text);
        self::assertSame('Database mysql://***@db.internal/sylius is unreachable', $text);
    }

    public function testAnExecutionTraceIsReplacedByAGenericMessage(): void
    {
        $text = $this->extension()->text(new TranslatableMessage(self::TRACE), 'data.demo');

        self::assertSame('The detail was hidden: see the application log.', $text);
    }

    public function testTheFaultyProbeIsLoggedByItsIdentifierWithoutTheTrace(): void
    {
        $this->extension()->text(new TranslatableMessage(self::TRACE), 'data.demo');

        self::assertCount(1, $this->logger->records);
        self::assertSame('data.demo', $this->logger->records[0]['context']['identifier'] ?? null);
        self::assertStringNotContainsString('Foo.php', json_encode($this->logger->records, \JSON_THROW_ON_ERROR));
    }

    public function testAHarmlessTextIsNotLogged(): void
    {
        $text = $this->extension()->text(new TranslatableMessage('Fine'), 'data.demo');

        self::assertSame('Fine', $text);
        self::assertSame([], $this->logger->records);
    }

    private function extension(): SafeTextExtension
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['tmonier_platform_health.result.detail_hidden' => 'The detail was hidden: see the application log.'], 'en');

        return new SafeTextExtension($translator, new SensitiveValueMasker('/var/www/shop'), $this->logger);
    }
}
