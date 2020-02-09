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
        Sequence $arguments
    ): self {
        return new self(
            $specification
                ->pattern()
                ->options()
                ->extract($arguments)
                ->toMapOf( // simply for a type change
                    'string',
                    'string',
                    static function(string $name, string $value): \Generator {
                        yield $name => $value;
                    },
                ),
        );
    }

    /**
     * @return string|bool
     */
    public function get(string $argument)
    {
        return $this->options->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->options->contains($argument);
    }
}
