<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\Security\SensitiveValueMasker;

final class SensitiveValueMaskerTest extends TestCase
{
    private const PROJECT_DIR = '/var/www/shop';

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function maskedTexts(): iterable
    {
        // Built at run time: no secret-shaped literal in the repository (scanners).
        $longKey = 'a1B2c3D4' . 'e5F6g7H8' . 'i9J0k1L2' . 'm3N4o5P6';

        yield 'dsn with user and password' => ['Database mysql://shop:s3cret@db.internal/sylius is unreachable', 'Database mysql://***@db.internal/sylius is unreachable'];
        yield 'dsn with password only' => ['Cache redis://:p4ss@cache:6379 is down', 'Cache redis://***@cache:6379 is down'];
        yield 'password pair' => ['connect password=hunter2 failed', 'connect password=*** failed'];
        yield 'quoted token pair' => ['token="a b c" rejected', 'token=*** rejected'];
        yield 'api key with colon' => ['API_KEY: abc123def rejected', 'API_KEY: *** rejected'];
        yield 'dsn pair' => ['MAILER_DSN=smtp://user:pass@mail:25', 'MAILER_DSN=***'];
        yield 'long key' => ['Found ' . $longKey . ' in the configuration', 'Found *** in the configuration'];
        yield 'absolute path inside the project' => ['Cannot read /var/www/shop/config/packages/doctrine.yaml', 'Cannot read config/packages/doctrine.yaml'];
        yield 'absolute path outside the project' => ['Cannot read /etc/ssl/private/server.pem', 'Cannot read .../server.pem'];
        yield 'plain sentence untouched' => ['PHP 8.3.4 is supported until 2027-12-31 (400 days left).', 'PHP 8.3.4 is supported until 2027-12-31 (400 days left).'];
        yield 'extension list untouched' => ['Missing: intl, gd.', 'Missing: intl, gd.'];
        yield 'admin route untouched' => ['Open /admin/platform-health to see more', 'Open /admin/platform-health to see more'];
        yield 'public url untouched' => ['See https://example.com/docs/page.html', 'See https://example.com/docs/page.html'];
    }

    #[DataProvider('maskedTexts')]
    public function testItMasksWhatASecretLooksLike(string $text, string $expected): void
    {
        self::assertSame($expected, (new SensitiveValueMasker(self::PROJECT_DIR))->mask($text));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function traces(): iterable
    {
        yield 'php stack trace' => ["Boom\nStack trace:\n#0 /var/www/shop/src/Foo.php(12): Foo->bar()\n#1 {main}", true];
        yield 'frame alone' => ['#0 /var/www/shop/src/Foo.php(12): Foo->bar()', true];
        yield 'main frame' => ["#2 {main}\n  thrown", true];
        yield 'stack trace header' => ['Stack trace:', true];
        yield 'numbered item' => ['Item #1 of 3 is missing', false];
        yield 'order number' => ['Order #0042 is late', false];
        yield 'plain sentence' => ['The check could not be completed.', false];
    }

    #[DataProvider('traces')]
    public function testItRecognisesAnExecutionTrace(string $text, bool $expected): void
    {
        self::assertSame($expected, (new SensitiveValueMasker(self::PROJECT_DIR))->looksLikeExecutionTrace($text));
    }
}
