<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\MissingArgument;
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
final class RequiredArgument implements Input, Argument
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
            ->filter(static fn($pattern) => $pattern->matches('~^[a-zA-Z0-9]+$~'))
            ->map(static fn($pattern) => new self($pattern->toString()));
    }

    #[\Override]
    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array {
        $value = $arguments->first()->match(
            static fn($value) => $value,
            fn() => throw new MissingArgument($this->name),
        );

        return [
            $arguments->drop(1),
            ($parsedArguments)($this->name, $value),
            $pack,
            $options,
        ];
    }

    #[\Override]
    public function toString(): string
    {
        return $this->name;
    }
}
