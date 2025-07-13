<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Command\Usage;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 * @internal
 */
final class PackArgument implements Input
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function walk(Usage $usage, Str $pattern): Maybe
    {
        return self::of($pattern)->map(static fn() => $usage->packArguments());
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $pattern): Maybe
    {
        /** @var Maybe<Input> */
        return Maybe::just($pattern)
            ->filter(static fn($pattern) => $pattern->matches('~^\.\.\.[a-zA-Z0-9]+$~'))
            ->map(static fn($pattern) => $pattern->drop(3))
            ->map(static fn($pattern) => new self);
    }
}
