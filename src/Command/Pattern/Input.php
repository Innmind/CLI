<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

/**
 * @psalm-immutable
 */
interface Input
{
    public static function of(Str $pattern): self;

    /**
     * @param Map<string, string|Sequence<string>> $parsed
     * @param Sequence<string> $arguments
     *
     * @return Map<string, string|Sequence<string>>
     */
    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments,
    ): Map;
    public function toString(): string;
}
