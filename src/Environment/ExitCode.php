<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

final class ExitCode
{
    private $code;

    public function __construct(int $code)
    {
        $code = $code < 0 ? 0 : $code;
        $code = $code > 254 ? 254 : $code; //255 is reserved by PHP
        $this->code = $code;
    }

    public function toInt(): int
    {
        return $this->code;
    }

    public function successful(): bool
    {
        return $this->code === 0;
    }
}
