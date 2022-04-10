<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

final class OptionalArgument implements Input, Argument
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function of(Str $pattern): Input
    {
        if (!$pattern->matches('~^\[[a-zA-Z0-9]+\]$~')) {
            throw new PatternNotRecognized($pattern->toString());
        }

        return new self($pattern->substring(1, -1)->toString());
    }

    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments,
    ): Map {
        return $arguments
            ->get($position)
            ->match(
                fn($argument) => ($parsed)($this->name, $argument),
                static fn() => $parsed,
            );
    }

    public function toString(): string
    {
        return '['.$this->name.']';
    }
}
