<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Pattern\Inputs,
    Command\Pattern\Input,
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

        $packs = $this->inputs->filter(static function(Input $input): bool {
            return $input instanceof PackArgument;
        });

        if ($packs->size() > 1) {
            throw new OnlyOnePackArgumentAllowed;
        }

        if ($packs->size() > 0 && !$this->inputs->last() instanceof PackArgument) {
            throw new PackArgumentMustBeTheLastOne;
        }

        $this->inputs->drop(1)->reduce(
            $this->inputs->take(1),
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
                static function(Map $inputs, int $position, Input $input) use ($arguments): Map {
                    return $input->extract($inputs, $position, $arguments);
                }
            );
    }

    public function __toString(): string
    {
        return (string) $this->inputs->join(' ');
    }
}
