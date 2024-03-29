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
final class PackArgument implements Input, Argument
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
            ->filter(static fn($pattern) => $pattern->matches('~^\.\.\.[a-zA-Z0-9]+$~'))
            ->map(static fn($pattern) => $pattern->drop(3))
            ->map(static fn($pattern) => new self($pattern->toString()));
    }

    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array {
        return [
            $arguments->clear(),
            $parsedArguments,
            $pack->append($arguments),
            $options,
        ];
    }

    public function toString(): string
    {
        return '...'.$this->name;
    }
}
