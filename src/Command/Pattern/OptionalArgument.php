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
final class OptionalArgument implements Input, Argument
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $pattern): Maybe
    {
        /** @var Maybe<Input> */
        return Maybe::just($pattern)
            ->filter(static fn($pattern) => $pattern->matches('~^\[[a-zA-Z0-9]+\]$~'))
            ->map(static fn($pattern) => $pattern->substring(1, -1))
            ->map(static fn($pattern) => new self($pattern->toString()));
    }

    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments,
    ): Map {
        return $arguments
            ->get($position)
            ->match(
                fn($argument) => ($parsed)($this->name, $argument),
                static fn() => $parsed,
            );
    }

    public function toString(): string
    {
        return '['.$this->name.']';
    }
}
