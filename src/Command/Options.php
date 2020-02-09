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
     * @param Map<string, mixed> $options
     */
    public function __construct(Map $options = null)
    {
        $options ??= Map::of('string', 'mixed');

        assertMap('string', 'mixed', $options, 1);

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
        );
    }

    /**
     * @deprecated
     * @see self::of()
     */
    public static function fromSpecification(
        Specification $specification,
        Sequence $arguments
    ): self {
        return self::of($specification, $arguments);
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
