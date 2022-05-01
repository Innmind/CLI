<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};

/**
 * @psalm-immutable
 */
final class PackArgument implements Input, Argument
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $pattern): Input
    {
        if (!$pattern->matches('~^\.\.\.[a-zA-Z0-9]+$~')) {
            throw new PatternNotRecognized($pattern->toString());
        }

        return new self($pattern->substring(3)->toString());
    }

    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments,
    ): Map {
        /** @psalm-suppress ArgumentTypeCoercion */
        return ($parsed)($this->name, $arguments->drop($position));
    }

    public function toString(): string
    {
        return '...'.$this->name;
    }
}
