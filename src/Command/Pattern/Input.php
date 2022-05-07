<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
    Maybe,
};

/**
 * @psalm-immutable
 */
interface Input
{
    /**
     * @return Maybe<self>
     */
    public static function of(Str $pattern): Maybe;

    /**
     * @param Sequence<string> $arguments
     * @param Map<string, string> $parsedArguments
     * @param Sequence<string> $pack
     * @param Map<string, string> $options
     *
     * @return array{
     *     Sequence<string>,
     *     Map<string, string>,
     *     Sequence<string>,
     *     Map<string, string>,
     * }
     */
    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array;

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
