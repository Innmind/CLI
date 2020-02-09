<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    StreamInterface,
    MapInterface,
};

final class PackArgument implements Input, Argument
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function fromString(Str $pattern): Input
    {
        if (!$pattern->matches('~^\.\.\.[a-zA-Z0-9]+$~')) {
            throw new PatternNotRecognized((string) $pattern);
        }

        return new self((string) $pattern->substring(3));
    }

    /**
     * {@inheritdoc}
     */
    public function extract(
        MapInterface $parsed,
        int $position,
        StreamInterface $arguments
    ): MapInterface {
        if (!$arguments->indices()->contains($position)) {
            return $parsed->put($this->name, $arguments->clear());
        }

        return $parsed->put($this->name, $arguments->drop($position));
    }

    public function __toString(): string
    {
        return '...'.$this->name;
    }
}
