<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\{
    assertMap,
    assertSequence,
};

final class Arguments
{
    private Map $arguments;
    private ?Sequence $pack = null;

    /**
     * @param Map<string, string> $arguments
     * @param Sequence<string> $pack
     */
    public function __construct(Map $arguments = null, Sequence $pack = null)
    {
        $arguments ??= Map::of('string', 'string');
        $pack ??= Sequence::strings();

        assertMap('string', 'string', $arguments, 1);
        assertSequence('string', $pack, 2);

        $this->arguments = $arguments;
        $this->pack = $pack;
    }

    /**
     * @param Sequence<string> $arguments
     */
    public static function of(
        Specification $specification,
        Sequence $arguments
    ): self {
        $arguments = $specification->pattern()->options()->clean($arguments);
        $arguments = $specification
            ->pattern()
            ->arguments()
            ->extract($arguments);

        try {
            $pack = $arguments->values()->find(
                static fn($argument): bool => $argument instanceof Sequence,
            );
        } catch (NoElementMatchingPredicateFound $e) {
            $pack = null;
        }

        $arguments = $arguments
            ->filter(static fn(string $_, $argument): bool => \is_string($argument))
            ->toMapOf(
                'string',
                'string',
                static function(string $key, string $argument): \Generator {
                    yield $key => $argument;
                },
            );

        return new self($arguments, $pack);
    }

    public function get(string $argument): string
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
