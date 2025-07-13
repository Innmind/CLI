<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

/**
 * @psalm-immutable
 */
final class ExitCode
{
    /**
     * @internal
     *
     * 255 is reserved by PHP
     * @param int<0, 254> $code
     */
    public function __construct(private int $code)
    {
    }

    /**
     * @return int<0, 254>
     */
    public function toInt(): int
    {
        return $this->code;
    }

    public function successful(): bool
    {
        return $this->code === 0;
    }
}
