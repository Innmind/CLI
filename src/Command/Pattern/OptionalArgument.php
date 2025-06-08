<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Command\Usage;
use Innmind\Validation\Is;
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
final class OptionalArgument implements Input
{
    /**
     * @param non-empty-string $name
     */
    private function __construct(private string $name)
    {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function named(string $name): self
    {
        return new self($name);
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function walk(Usage $usage, Str $pattern): Maybe
    {
        return self::of($pattern)
            ->map(static fn($self) => $self->name)
            ->map($usage->optionalArgument(...));
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Str $pattern): Maybe
    {
        return Maybe::just($pattern)
            ->filter(static fn($pattern) => $pattern->matches('~^\[[a-zA-Z0-9]+\]$~'))
            ->map(static fn($pattern) => $pattern->drop(1)->dropEnd(1)->toString())
            ->keep(Is::string()->nonEmpty()->asPredicate())
            ->map(static fn($pattern) => new self($pattern));
    }

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

    public function toString(): string
    {
        return '['.$this->name.']';
    }
}
