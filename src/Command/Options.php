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

    public function get(string $option): string
    {
        return $this->maybe($option)->match(
            static fn($value) => $value,
            static fn() => throw new \RuntimeException,
        );
    }

    /**
     * @return Maybe<string>
     */
    public function maybe(string $option): Maybe
    {
        return $this->options->get($option);
    }

    public function contains(string $option): bool
    {
        return $this->options->contains($option);
    }
}
