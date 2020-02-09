<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\{
    PatternNotRecognized,
    MissingArgument,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

final class RequiredArgument implements Input, Argument
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function fromString(Str $pattern): Input
    {
        if (!$pattern->matches('~^[a-zA-Z0-9]+$~')) {
            throw new PatternNotRecognized($pattern->toString());
        }

        return new self($pattern->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments
    ): Map {
        if (!$arguments->indices()->contains($position)) {
            throw new MissingArgument($this->name);
        }

        return $parsed->put($this->name, $arguments->get($position));
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
