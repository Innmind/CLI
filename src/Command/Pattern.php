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
    StreamInterface,
    Stream,
    MapInterface,
    Map,
};

final class Pattern
{
    private $inputs;

    public function __construct(Str ...$inputs)
    {
        $loader = new Inputs;
        $this->inputs = Sequence::of(...$inputs)->reduce(
            Stream::of(Input::class),
            static function(Stream $inputs, Str $element) use ($loader): Stream {
                return $inputs->add($loader->load($element));
            }
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

        if ($packs->size() > 0 && !$arguments->last() instanceof PackArgument) {
            throw new PackArgumentMustBeTheLastOne;
        }

        $arguments->drop(1)->reduce(
            $arguments->take(1),
            static function(Stream $inputs, Input $input): Stream {
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
     * @param StreamInterface<string> $arguments
     *
     * @return MapInterface<string, mixed>
     */
    public function extract(StreamInterface $arguments): MapInterface
    {
        return $this
            ->inputs
            ->reduce(
                new Map('int', Input::class),
                static function(Map $inputs, Input $input): Map {
                    return $inputs->put($inputs->size(), $input); //map value to a position
                }
            )
            ->reduce(
                new Map('string', 'mixed'),
                static function(Map $inputs, int $position, Input $input) use ($arguments): MapInterface {
                    return $input->extract($inputs, $position, $arguments);
                }
            );
    }

    public function __toString(): string
    {
        return (string) $this->inputs->join(' ');
    }
}
