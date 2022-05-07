<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
    Maybe,
};

/**
 * @psalm-immutable
 */
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
     * @psalm-pure
     *
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
        return $this->maybe($argument)->match(
            static fn($value) => $value,
            static fn() => throw new \RuntimeException,
        );
    }

    /**
     * @return Maybe<string>
     */
    public function maybe(string $argument): Maybe
    {
        return $this->options->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->options->contains($argument);
    }
}
