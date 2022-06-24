<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

/**
 * @psalm-immutable
 */
final class ExitCode
{
    /**
     * 255 is reserved by PHP
     * @var int<0, 254>
     */
    private int $code;

    /**
     * @param int<0, 254> $code
     */
    public function __construct(int $code)
    {
        $this->code = $code;
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
