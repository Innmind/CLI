<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    MapInterface,
    Map,
    StreamInterface,
};

final class Options
{
    private $options;

    /**
     * @param MapInterface<string, mixed> $options
     */
    public function __construct(MapInterface $options = null)
    {
        $options = $options ?? new Map('string', 'mixed');

        if (
            (string) $options->keyType() !== 'string' ||
            (string) $options->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $this->options = $options;
    }

    /**
     * @param StreamInterface<string> $arguments
     */
    public static function of(
        Specification $specification,
        StreamInterface $arguments
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
        StreamInterface $arguments
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
