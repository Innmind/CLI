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
 * @internal
 */
final class OptionalArgument implements Input, Argument
{
    private function __construct(public string $name)
    {
    }

    /**
     * @psalm-pure
     */
    public static function named(string $name): self
    {
        return new self($name);
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function of(Str $pattern): Maybe
    {
        /** @var Maybe<Input> */
        return Maybe::just($pattern)
            ->filter(static fn($pattern) => $pattern->matches('~^\[[a-zA-Z0-9]+\]$~'))
            ->map(static fn($pattern) => $pattern->drop(1)->dropEnd(1))
            ->map(static fn($pattern) => new self($pattern->toString()));
    }

    #[\Override]
    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array {
        [$arguments, $parsedArguments] = $arguments
            ->first()
            ->match(
                fn($value) => [
                    $arguments->drop(1),
                    ($parsedArguments)($this->name, $value),
                ],
                static fn() => [$arguments, $parsedArguments],
            );

        return [$arguments, $parsedArguments, $pack, $options];
    }

    #[\Override]
    public function toString(): string
    {
        return '['.$this->name.']';
    }
}
