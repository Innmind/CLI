<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Pattern\Input,
    Command\Pattern\Argument,
    Command\Pattern\Option,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Pattern
{
    /**
     * @param Sequence<Input> $inputs
     */
    public function __construct(private Sequence $inputs)
    {
    }

    /**
     * @param Sequence<string> $arguments
     *
     * @return array{Arguments, Options}
     */
    public function __invoke(Sequence $arguments): array
    {
        /** @var Map<string, string> */
        $parsedArguments = Map::of();
        $pack = Sequence::strings();
        /** @var Map<string, string> */
        $options = Map::of();

        // parse the arguments after the options as the options can be anywhere
        // in the sequence
        $inputs = $this
            ->inputs
            ->filter(static fn($input) => $input instanceof Option)
            ->append($this->inputs->filter(
                static fn($input) => $input instanceof Argument,
            ));

        /** @psalm-suppress MixedArgument */
        [$_, $parsedArguments, $pack, $options] = $inputs->reduce(
            [$arguments, $parsedArguments, $pack, $options],
            static fn($carry, $input) => $input->parse(...$carry),
        );

        return [new Arguments($parsedArguments, $pack), new Options($options)];
    }

    public function toString(): string
    {
        return Str::of(' ')
            ->join($this->inputs->map(static fn($input) => $input->toString()))
            ->toString();
    }
}
