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
    Maybe,
};

/**
 * @psalm-immutable
 * @internal
 */
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
            ->map(static fn($line) => $line->split(' '))
            ->flatMap(static fn($parts) => $parts->first())
            ->match(
                static fn($name) => $name->toString(),
                static fn() => throw new \LogicException('Command name not found'),
            );
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
        $name = Str::of($this->name())->trim(':');

        if ($name->equals($command)) {
            return true;
        }

        $commandChunks = $command->split(':');
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var Sequence<Str>
         */
        $nameChunks = $name
            ->split(':')
            ->reduce(
                Sequence::of(),
                static fn(Sequence $names, $chunk) => $commandChunks
                    ->get($names->size())
                    ->map(static fn($chunk) => $chunk->length())
                    ->map(static fn($length) => $chunk->take($length))
                    ->match(
                        static fn($chunk) => ($names)($chunk),
                        static fn() => ($names)($chunk),
                    ),
            );

        if ($nameChunks->size() !== $commandChunks->size()) {
            return false;
        }

        return $nameChunks
            ->map(static fn($chunk) => $chunk->toString())
            ->diff($commandChunks->map(static fn($chunk) => $chunk->toString()))
            ->empty();
    }

    public function shortDescription(): string
    {
        return $this
            ->lines()
            ->get(2)
            ->map(static fn($line) => $line->trim()->toString())
            ->match(
                static fn($description) => $description,
                static fn() => '',
            );
    }

    public function description(): string
    {
        $lines = $this
            ->lines()
            ->drop(4)
            ->map(static fn($line) => $line->trim()->toString());

        return Str::of("\n")->join($lines)->toString();
    }

    public function pattern(): Pattern
    {
        return new Pattern(
            ...$this
                ->firstLine()
                ->map(static fn($line) => $line->split(' ')->drop(1))
                ->match(
                    static fn($parts) => $parts->toList(),
                    static fn() => [],
                ),
        );
    }

    public function toString(): string
    {
        return $this
            ->firstLine()
            ->match(
                static fn($line) => $line->toString(),
                static fn() => '',
            );
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

    /**
     * @return Maybe<Str>
     */
    private function firstLine(): Maybe
    {
        return $this
            ->lines()
            ->first()
            ->map(static fn($line) => $line->append(' --help --no-interaction'));
    }
}
