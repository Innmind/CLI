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

    public function options(): self
    {
        $self = clone $this;
        $self->inputs = $self->inputs->filter(static function(Input $input): bool {
            return $input instanceof Option;
        });

        return $self;
    }

    public function arguments(): self
    {
        $self = clone $this;
        $self->inputs = $self->inputs->filter(static function(Input $input): bool {
            return $input instanceof Argument;
        });

        return $self;
    }

    /**
     * @param Sequence<string> $arguments
     *
     * @return Map<string, string|Sequence<string>>
     */
    public function extract(Sequence $arguments): Map
    {
        /** @var Map<int, Input> */
        $valueToPosition = $this->inputs->reduce(
            Map::of(),
            static function(Map $inputs, Input $input): Map {
                return ($inputs)($inputs->size(), $input); //map value to a position
            },
        );

        /** @var Map<string, string|Sequence<string>> */
        return $valueToPosition->reduce(
            Map::of(),
            static function(Map $inputs, int $position, Input $input) use ($arguments): Map {
                /** @var Map<string, string|Sequence<string>> $inputs */
                return $input->extract($inputs, $position, $arguments);
            },
        );
    }

    /**
     * Remove all options from the list of arguments so the arguments can be
     * correctly extracted
     *
     * @param Sequence<string> $arguments
     *
     * @return Sequence<string>
     */
    public function clean(Sequence $arguments): Sequence
    {
        /**
         * @psalm-suppress InvalidArgument Would need refactoring to correctly isolate options before clean
         * @var Sequence<string>
         */
        return $this->inputs->reduce(
            $arguments,
            static function(Sequence $arguments, Option $option): Sequence {
                /** @var Sequence<string> $arguments */
                return $option->clean($arguments);
            },
        );
    }

    public function toString(): string
    {
        return Str::of(' ')
            ->join($this->inputs->map(static fn($input) => $input->toString()))
            ->toString();
    }
}
