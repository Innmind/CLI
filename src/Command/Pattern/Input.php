<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

interface Input
{
    public static function fromString(Str $pattern): self;

    /**
     * @param Map<string, mixed> $parsed
     * @param Sequence<string> $arguments
     *
     * @return Map<string, mixed>
     */
    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments
    ): Map;
    public function toString(): string;
}
