<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\Command;
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 * @internal
 */
final class Specification
{
    public function __construct(
        private Command $command,
        private ?Usage $parsed = null,
    ) {
    }

    public function name(): string
    {
        return $this->parse()->name();
    }

    public function is(string $command): bool
    {
        return $this->name() === $command;
    }

    public function matches(string $command): bool
    {
        if ($command === '') {
            return false;
        }

        $command = Str::of($command);
        $name = Str::of($this->name());

        if ($name->equals($command)) {
            return true;
        }

        $commandChunks = $command->trim(':')->split(':');
        $nameChunks = $name->trim(':')->split(':');
        $diff = $nameChunks
            ->zip($commandChunks)
            ->map(static fn($pair) => [
                $pair[0]->take($pair[1]->length()),
                $pair[1],
            ]);

        if ($nameChunks->size() !== $diff->size()) {
            return false;
        }

        return $diff->matches(
            static fn($pair) => $pair[0]->equals($pair[1]),
        );
    }

    public function shortDescription(): string
    {
        return $this->parse()->shortDescription();
    }

    public function pattern(): Pattern
    {
        return $this->parse()->pattern();
    }

    public function usage(): Usage
    {
        return $this->parse();
    }

    private function parse(): Usage
    {
        /** @psalm-suppress InaccessibleProperty */
        return $this->parsed ??= Usage::parse($this->command->usage());
    }
}
