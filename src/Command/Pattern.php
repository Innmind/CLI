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
use function Innmind\Immutable\join;

final class Pattern
{
    private Sequence $inputs;

    public function __construct(Str ...$inputs)
    {
        $load = new Inputs;
        $this->inputs = Sequence::of(Str::class, ...$inputs)->mapTo(
            Input::class,
            static fn(Str $element): Input => $load($element),
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

        if (!$packs->empty() && !$arguments->last() instanceof PackArgument) {
            throw new PackArgumentMustBeTheLastOne;
        }

        $arguments->drop(1)->reduce(
            $arguments->take(1),
            static function(Sequence $inputs, Input $input): Sequence {
                if (
                    $inputs->last() instanceof OptionalArgument &&
                    $input instanceof RequiredArgument
                ) {
                    throw new NoRequiredArgumentAllowedAfterAnOptionalOne;
                }

                return $inputs->add($input);
            }
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
        /** @var Map<string, string|Sequence<string>> */
        return $this
            ->inputs
            ->reduce(
                Map::of('int', Input::class),
                static function(Map $inputs, Input $input): Map {
                    return $inputs->put($inputs->size(), $input); //map value to a position
                }
            )
            ->reduce(
                Map::of('string', 'string|'.Sequence::class),
                static function(Map $inputs, int $position, Input $input) use ($arguments): Map {
                    /** @var Map<string, string|Sequence<string>> $inputs */
                    return $input->extract($inputs, $position, $arguments);
                }
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
        /** @var Sequence<string> */
        return $this->inputs->reduce(
            $arguments,
            static function(Sequence $arguments, Option $option): Sequence {
                /** @var Sequence<string> $arguments */
                return $option->clean($arguments);
            }
        );
    }

    public function toString(): string
    {
        return join(
            ' ',
            $this->inputs->mapTo(
                'string',
                static fn(Input $input): string => $input->toString(),
            ),
        )->toString();
    }
}
