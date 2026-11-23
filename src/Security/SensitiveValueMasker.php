<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Security;

/**
 * Defence in depth: probes are written by third parties, so the page masks what a probe let slip.
 * It does not replace the design rule that a probe produces no sensitive value in the first place.
 */
final readonly class SensitiveValueMasker
{
    private const MASK = '***';

    /** Credentials of a DSN: what sits between "://" and the "@". */
    private const DSN_CREDENTIALS = '~(?<=://)[^\s/]*@~';

    /** A "name=value" or "name: value" pair whose name evokes a secret; the value may be quoted. */
    private const SECRET_PAIR = '~\b([\w.-]*(?:secret|password|passwd|pwd|token|key|dsn)[\w.-]*)(\s*[=:]\s*)("[^"]*"|\'[^\']*\'|[^\s,;&]+)~i';

    /** A file outside the project: at least two directories and a file name with an extension. */
    private const FOREIGN_FILE = '~(?<![\w:/.@+-])(?:/[\w.@+-]+)+/([\w.@+-]+\.[A-Za-z0-9]{1,8})(?![\w/])~';

    /** 32 characters or more, with at least one digit: the shape of an API key. */
    private const LONG_KEY = '~(?<![\w/.-])(?=[A-Za-z_-]*\d)[A-Za-z0-9_-]{32,}(?![\w/.-])~';

    private const TRACE = '~^\s*#\d+\s+(?:\{main\}|\[internal function\]|\S+\(\d+\))|Stack trace:~mi';

    public function __construct(
        private string $projectDir,
    ) {
    }

    public function mask(string $text): string
    {
        $text = $this->relativeToProject($text);
        $text = (string) preg_replace(self::DSN_CREDENTIALS, self::MASK . '@', $text);
        $text = (string) preg_replace(self::SECRET_PAIR, '$1$2' . self::MASK, $text);
        $text = (string) preg_replace(self::FOREIGN_FILE, '.../$1', $text);

        return (string) preg_replace(self::LONG_KEY, self::MASK, $text);
    }

    public function looksLikeExecutionTrace(string $text): bool
    {
        return 1 === preg_match(self::TRACE, $text);
    }

    private function relativeToProject(string $text): string
    {
        $directory = rtrim($this->projectDir, '/');

        return '' === $directory ? $text : str_replace($directory . '/', '', $text);
    }
}
