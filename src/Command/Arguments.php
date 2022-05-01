<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
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
     * @param Map<string, string> $arguments
     * @param Sequence<string> $pack
     */
    public function __construct(Map $arguments = null, Sequence $pack = null)
    {
        $this->arguments = $arguments ?? Map::of();
        $this->pack = $pack ?? Sequence::strings();
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
        $arguments = $specification->pattern()->options()->clean($arguments);
        $arguments = $specification
            ->pattern()
            ->arguments()
            ->extract($arguments);

        /** @var ?Sequence<string> */
        $pack = $arguments
            ->values()
            ->find(static fn($argument): bool => $argument instanceof Sequence)
            ->match(
                static fn($pack) => $pack,
                static fn() => null,
            );

        /** @psalm-suppress InvalidArgument */
        $arguments = $arguments
            ->filter(static fn(string $_, $argument): bool => \is_string($argument))
            ->flatMap(static fn(string $key, string $value) => Map::of([$key, $value]));

        return new self($arguments, $pack);
    }

    public function get(string $argument): string
    {
        return $this->arguments->get($argument)->match(
            static fn($value) => $value,
            static fn() => throw new \RuntimeException,
        );
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
