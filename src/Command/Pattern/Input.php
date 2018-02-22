<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\{
    Str,
    StreamInterface,
    MapInterface,
};

interface Input
{
    public static function fromString(Str $pattern): self;

    /**
     * @param MapInterface<string, mixed> $parsed
     * @param StreamInterface<string> $arguments
     *
     * @return MapInterface<string, mixed>
     */
    public function extract(
        MapInterface $parsed,
        int $position,
        StreamInterface $arguments
    ): MapInterface;
    public function __toString(): string;
}
