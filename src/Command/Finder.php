<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\Command;
use Innmind\Immutable\{
    Sequence,
    Pair,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Finder
{
    /**
     * @param Command|Sequence<Pair<Usage, Command>>|null $found
     * @param Sequence<Usage> $all
     */
    private function __construct(
        private Command|Sequence|null $found,
        private Sequence $all,
    ) {
    }

    public static function new(): self
    {
        return new self(null, Sequence::of());
    }

    public function maybe(Command $maybe, string $command): self
    {
        // Prevent overriding if a command has already been found
        if ($this->found instanceof Command) {
            return $this;
        }

        $usage = $maybe->usage();

        if ($usage->is($command)) {
            return new self(
                $maybe,
                Sequence::of(),
            );
        }

        if ($usage->matches($command)) {
            if ($this->found instanceof Sequence) {
                $found = $this->found->add(new Pair($usage, $maybe));
            } else {
                $found = Sequence::of(new Pair($usage, $maybe));
            }

            return new self(
                $found,
                Sequence::of(),
            );
        }

        return new self(
            $this->found,
            ($this->all)($usage),
        );
    }

    /**
     * @template R
     *
     * @param callable(self): R $match
     * @param callable(self): R $other
     *
     * @return R
     */
    public function next(callable $match, callable $other): mixed
    {
        if ($this->found instanceof Command) {
            /** @psalm-suppress ImpureFunctionCall */
            return $match($this);
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $other($this);
    }

    /**
     * @template R
     *
     * @param callable(Command): R $match
     * @param callable(Sequence<Usage>): R $matches
     *
     * @return R
     */
    public function match(callable $match, callable $matches): mixed
    {
        if ($this->found instanceof Command) {
            /** @psalm-suppress ImpureFunctionCall */
            return $match($this->found);
        }

        if (\is_null($this->found)) {
            /** @psalm-suppress ImpureFunctionCall */
            return $matches($this->all);
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $this->found->match(
            static fn($first, $rest) => match ($rest->empty()) {
                true => $match($first->value()),
                false => $matches(
                    Sequence::of($first)
                        ->append($rest)
                        ->map(static fn($pair) => $pair->key()),
                ),
            },
            fn() => $matches($this->all),
        );
    }
}
