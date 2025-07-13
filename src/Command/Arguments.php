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
final class Arguments
{
    /** @var Map<string, string> */
    private Map $arguments;
    /** @var Sequence<string> */
    private Sequence $pack;

    /**
     * @internal
     * @param Map<string, string> $arguments
     * @param Sequence<string> $pack
     */
    public function __construct(?Map $arguments = null, ?Sequence $pack = null)
    {
        $this->arguments = $arguments ?? Map::of();
        $this->pack = $pack ?? Sequence::strings();
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
        return $this->arguments->get($argument);
    }

    /**
     * @return Sequence<string>
     */
    public function pack(): Sequence
    {
        return $this->pack;
    }

    public function contains(string $argument): bool
    {
        return $this->arguments->contains($argument);
    }
}
