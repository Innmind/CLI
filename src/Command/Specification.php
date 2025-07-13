<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\Command;

/**
 * @psalm-immutable
 * @internal
 */
final class Specification
{
    public function __construct(
        private Command $command,
    ) {
    }

    public function name(): string
    {
        return $this->usage()->name();
    }

    public function is(string $command): bool
    {
        return $this->usage()->is($command);
    }

    public function matches(string $command): bool
    {
        return $this->usage()->matches($command);
    }

    public function shortDescription(): string
    {
        return $this->usage()->shortDescription();
    }

    public function pattern(): Pattern
    {
        return $this->usage()->pattern();
    }

    public function usage(): Usage
    {
        return $this->command->usage();
    }
}
