<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
};
use function Innmind\Immutable\assertMap;

final class Options
{
    /** @var Map<string, string> */
    private Map $options;

    /**
     * @param Map<string, string> $options
     */
    public function __construct(Map $options = null)
    {
        $options ??= Map::of('string', 'string');

        assertMap('string', 'string', $options, 1);

        $this->options = $options;
    }

    /**
     * @param Sequence<string> $arguments
     */
    public static function of(
        Specification $specification,
        Sequence $arguments,
    ): self {
        /** @var Map<string, string> */
        $options = $specification
            ->pattern()
            ->options()
            ->extract($arguments)
            ->toMapOf( // simply for a type change
                'string',
                'string',
                static function(string $name, $value): \Generator {
                    yield $name => $value;
                },
            );

        return new self($options);
    }

    public function get(string $argument): string
    {
        return $this->options->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->options->contains($argument);
    }
}
