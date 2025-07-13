<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Usage,
    Exception\Exception,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Attempt,
};

final class Commands
{
    /** @var Sequence<Command> */
    private Sequence $commands;

    private function __construct(Command $command, Command ...$commands)
    {
        $this->commands = Sequence::of($command, ...$commands);
    }

    /**
     * @return Attempt<Environment>
     */
    public function __invoke(Environment $env): Attempt
    {
        return $this->commands->match(
            fn($command, $rest) => match ($rest->empty()) {
                true => $this->run($env, $command),
                false => $this->find($env), // todo avoid re-iterating over commands
            },
            fn() => $this->find($env),
        );
    }

    public static function of(Command $command, Command ...$commands): self
    {
        return new self($command, ...$commands);
    }

    /**
     * @return Attempt<Environment>
     */
    private function find(Environment $env): Attempt
    {
        $command = $env
            ->arguments()
            ->get(1) // 0 being the tool name
            ->match(
                static fn($command) => $command,
                static fn() => null,
            );

        if (!\is_string($command)) {
            return $this
                ->displayHelp(
                    $env,
                    true,
                    $this->commands->map(static fn($command) => $command->usage()),
                )
                ->map(static fn($env) => $env->exit(64)); // EX_USAGE The command was used incorrectly
        }

        if ($command === 'help') {
            return $this->displayHelp(
                $env,
                false,
                $this->commands->map(static fn($command) => $command->usage()),
            );
        }

        /** @var Sequence<Command> */
        $found = Sequence::of();
        $commands = $this
            ->commands
            ->sink($found)
            ->until(static fn($found, $maybe, $continuation) => match ($maybe->usage()->is($command)) {
                true => $continuation->stop(Sequence::of($maybe)),
                false => match ($maybe->usage()->matches($command)) {
                    true => $continuation->continue(($found)($maybe)),
                    false => $continuation->continue($found),
                },
            });

        return $commands->match(
            fn($command, $rest) => match ($rest->empty()) {
                true => $this->run($env, $command),
                false => $this
                    ->displayHelp(
                        $env,
                        true,
                        Sequence::of($command)
                            ->append($rest)
                            ->map(static fn($command) => $command->usage()),
                    )
                    ->map(static fn($env) => $env->exit(64)), // EX_USAGE The command was used incorrectly
            },
            fn() => $this
                ->displayHelp(
                    $env,
                    true,
                    $this->commands->map(static fn($command) => $command->usage()),
                )
                ->map(static fn($env) => $env->exit(64)), // EX_USAGE The command was used incorrectly
        );
    }

    /**
     * @return Attempt<Environment>
     */
    private function run(Environment $env, Command $command): Attempt
    {
        $usage = $command->usage();
        [$bin, $arguments] = $env->arguments()->match(
            static fn($bin, $arguments) => [$bin, $arguments],
            static fn() => throw new \LogicException('Arguments list should not be empty'),
        );

        // drop command name, conditional as it can be omitted when only one
        // command defined
        $arguments = $arguments
            ->first()
            ->filter(static fn($first) => $usage->matches($first))
            ->match(
                static fn() => $arguments->drop(1),
                static fn() => $arguments,
            );

        if ($arguments->contains('--help')) {
            return $this->displayUsage(
                $env->output(...),
                $bin,
                $usage,
            );
        }

        try {
            $pattern = $usage->pattern();

            [$arguments, $options] = $pattern($arguments);
        } catch (Exception $e) {
            return $this
                ->displayUsage(
                    $env->error(...),
                    $bin,
                    $usage,
                )
                ->map(static fn($env) => $env->exit(64)); // EX_USAGE The command was used incorrectly
        }

        return $command(Console::of($env, $arguments, $options))->map(
            static fn($console) => $console->environment(),
        );
    }

    /**
     * @param callable(Str): Attempt<Environment> $write
     *
     * @return Attempt<Environment>
     */
    private function displayUsage(
        callable $write,
        string $bin,
        Usage $usage,
    ): Attempt {
        return $write(
            Str::of('usage: ')
                ->append($bin)
                ->append(' ')
                ->append($usage->toString())
                ->append("\n"),
        );
    }

    /**
     * @param Sequence<Usage> $usages
     *
     * @return Attempt<Environment>
     */
    private function displayHelp(
        Environment $env,
        bool $error,
        Sequence $usages,
    ): Attempt {
        $names = $usages->map(
            static fn($usage) => Str::of($usage->name()),
        );
        $lengths = $names
            ->map(static fn($name) => $name->length())
            ->toList();
        /** @var positive-int */
        $maxLength = \max(...$lengths);

        $rows = $usages->map(
            static fn($usage) => Str::of(' ')
                ->append(Str::of($usage->name())->rightPad($maxLength)->toString())
                ->append('  ')
                ->append($usage->shortDescription())
                ->append("\n"),
        );

        if ($error) {
            return $rows
                ->sink($env)
                ->attempt(static fn($env, $row) => $env->error($row));
        }

        return $rows
            ->sink($env)
            ->attempt(static fn($env, $row) => $env->output($row));
    }
}
