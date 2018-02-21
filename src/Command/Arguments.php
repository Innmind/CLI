<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    MapInterface,
    Map,
    StreamInterface,
};

final class Arguments
{
    private $arguments;

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function __construct(MapInterface $arguments = null)
    {
        $arguments = $arguments ?? new Map('string', 'mixed');

        if (
            (string) $arguments->keyType() !== 'string' ||
            (string) $arguments->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $this->arguments = $arguments;
    }

    /**
     * @return string|StreamInterface<string>
     */
    public function get(string $argument)
    {
        return $this->arguments->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->arguments->contains($argument);
    }
}
