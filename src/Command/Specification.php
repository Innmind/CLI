<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command,
    Command\Pattern\Inputs,
    Command\Pattern\RequiredArgument,
    Command\Pattern\OptionalArgument,
    Command\Pattern\PackArgument,
    Command\Pattern\OptionFlag,
    Command\Pattern\OptionWithValue,
    Exception\EmptyDeclaration,
};
use Innmind\Validation\Is;
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

    private function parse(): Usage
    {
        if ($this->parsed) {
            return $this->parsed;
        }

        $lines = $this->lines();
        $name = $lines
            ->first()
            ->map(static fn($line) => $line->split(' '))
            ->flatMap(static fn($parts) => $parts->first())
            ->map(static fn($name) => $name->toString())
            ->keep(Is::string()->nonEmpty()->asPredicate())
            ->match(
                static fn($name) => $name,
                static fn() => throw new \LogicException('Command name not found'),
            );
        $usage = Usage::of($name);
        $usage = $lines
            ->get(2)
            ->map(static fn($line) => $line->trim()->toString())
            ->match(
                $usage->withShortDescription(...),
                static fn() => $usage,
            );

        $description = $lines
            ->drop(4)
            ->map(static fn($line) => $line->trim()->toString());
        $description = Str::of("\n")->join($description)->toString();

        if ($description !== '') {
            $usage = $usage->withDescription($description);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $usage = $lines
            ->first()
            ->toSequence()
            ->flatMap(static fn($line) => $line->split(' ')->drop(1))
            ->map(new Inputs)
            ->reduce(
                $usage,
                static fn(Usage $usage, $input) => match (true) {
                    $input instanceof RequiredArgument => $usage->argument($input->name),
                    $input instanceof OptionalArgument => $usage->optionalArgument($input->name),
                    $input instanceof PackArgument => $usage->packArguments(),
                    $input instanceof OptionFlag => $usage->flag($input->name, $input->short),
                    $input instanceof OptionWithValue => $usage->option($input->name, $input->short),
                },
            );

        /** @psalm-suppress InaccessibleProperty */
        return $this->parsed = $usage;
    }
}
