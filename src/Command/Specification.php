<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command,
    Exception\EmptyDeclaration,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class Specification
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function name(): string
    {
        return $this
            ->lines()
            ->first()
            ->split(' ')
            ->first()
            ->toString();
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

        $commandChunks = $command->split(':');
        $nameChunks = $name->split(':');

        if ($commandChunks->size() !== $nameChunks->size()) {
            return false;
        }

        try {
            $nameChunks->reduce(
                $commandChunks,
                static function(Sequence $command, Str $chunk): Sequence {
                    /** @var Str */
                    $current = $command->first();

                    if (!$chunk->take($current->length())->equals($current)) {
                        throw new \Exception('Chunks don\'t match');
                    }

                    return $command->drop(1);
                },
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function shortDescription(): string
    {
        $lines = $this->lines();

        // there must be a blank line before the short description
        if ($lines->size() < 3) {
            return '';
        }

        return $lines->get(2)->trim()->toString();
    }

    public function description(): string
    {
        $lines = $this->lines();

        // there must be a blank line before the description
        if ($lines->size() < 5) {
            return '';
        }

        $lines = $lines
            ->drop(4)
            ->map(static function(Str $line): Str {
                return $line->trim();
            })
            ->mapTo(
                'string',
                static fn(Str $line): string => $line->toString(),
            );

        return join("\n", $lines)->toString();
    }

    public function pattern(): Pattern
    {
        return new Pattern(...unwrap(
            $this
                ->lines()
                ->first()
                ->split(' ')
                ->drop(1),
        ));
    }

    public function toString(): string
    {
        return $this
            ->lines()
            ->first()
            ->toString();
    }

    /**
     * @return Sequence<Str>
     */
    private function lines(): Sequence
    {
        $declaration = Str::of($this->command->usage())->trim();

        if ($declaration->empty()) {
            throw new EmptyDeclaration(\get_class($this->command));
        }

        return $declaration->split("\n");
    }
}
