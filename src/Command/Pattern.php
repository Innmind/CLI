<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Pattern\Inputs,
    Command\Pattern\Input,
    Command\Pattern\Argument,
    Command\Pattern\Option,
    Command\Pattern\PackArgument,
    Command\Pattern\RequiredArgument,
    Command\Pattern\OptionalArgument,
    Exception\OnlyOnePackArgumentAllowed,
    Exception\PackArgumentMustBeTheLastOne,
    Exception\NoRequiredArgumentAllowedAfterAnOptionalOne,
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
    /** @var Sequence<Input> */
    private Sequence $inputs;

    /**
     * @no-named-arguments
     */
    public function __construct(Str ...$inputs)
    {
        $load = new Inputs;
        $this->inputs = Sequence::of(...$inputs)->map(
            static fn($element) => $load($element),
        );

        $arguments = $this->inputs->filter(static function(Input $input): bool {
            return $input instanceof Argument;
        });

        $packs = $arguments->filter(static function(Input $input): bool {
            return $input instanceof PackArgument;
        });

        if ($packs->size() > 1) {
            throw new OnlyOnePackArgumentAllowed;
        }

        $_ = $arguments
            ->last()
            ->filter(static fn() => !$packs->empty())
            ->filter(static fn($last) => !$last instanceof PackArgument)
            ->match(
                static fn() => throw new PackArgumentMustBeTheLastOne,
                static fn() => null,
            );

        $_ = $arguments->drop(1)->reduce(
            $arguments->take(1),
            static function(Sequence $inputs, Input $input): Sequence {
                $_ = $inputs
                    ->last()
                    ->filter(static fn($last) => $last instanceof OptionalArgument)
                    ->filter(static fn() => $input instanceof RequiredArgument)
                    ->match(
                        static fn() => throw new NoRequiredArgumentAllowedAfterAnOptionalOne,
                        static fn() => null,
                    );

                return ($inputs)($input);
            },
        );
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
