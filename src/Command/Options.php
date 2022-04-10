<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
};

final class Options
{
    /** @var Map<string, string> */
    private Map $options;

    /**
     * @param Map<string, string> $options
     */
    public function __construct(Map $options = null)
    {
        $this->options = $options ?? Map::of();
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
            ->extract($arguments);

        return new self($options);
    }

    public function get(string $argument): string
    {
        return $this->options->get($argument)->match(
            static fn($value) => $value,
            static fn() => throw new \RuntimeException,
        );
    }

    public function contains(string $argument): bool
    {
        return $this->options->contains($argument);
    }
}
