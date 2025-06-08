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
    public function __construct(private Command $command)
    {
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
