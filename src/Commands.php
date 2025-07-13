<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Usage,
    Command\Finder,
    Exception\Exception,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Attempt,
};

final class Commands
{
    /**
     * @param Command|Sequence<Command> $commands
     */
    private function __construct(
        private Command|Sequence $commands,
    ) {
    }

    /**
     * @return Attempt<Environment>
     */
    public function __invoke(Environment $env): Attempt
    {
        if ($this->commands instanceof Command) {
            return self::run($env, $this->commands);
        }

        return self::find($env, $this->commands);
    }

    public static function of(Command $command, Command ...$commands): self
    {
        return new self(match ($commands) {
            [] => $command,
            default => Sequence::of($command, ...$commands),
        });
    }

    /**
     * @param Sequence<Command> $commands
     *
     * @return Attempt<Environment>
     */
    private static function find(Environment $env, Sequence $commands): Attempt
    {
        $command = $env
            ->arguments()
            ->get(1) // 0 being the tool name
            ->match(
                static fn($command) => $command,
                static fn() => null,
            );

        if (!\is_string($command)) {
            return self::displayHelp(
                $env,
                true,
                $commands->map(static fn($command) => $command->usage()),
            )
                ->map(static fn($env) => $env->exit(64)); // EX_USAGE The command was used incorrectly
        }

        if ($command === 'help') {
            return self::displayHelp(
                $env,
                false,
                $commands->map(static fn($command) => $command->usage()),
            );
        }

        /** @var Sequence<Command> */
        $found = Sequence::of();

        return $commands
            ->sink(Finder::new())
            ->until(
                static fn($finder, $maybe, $continuation) => $finder
                    ->maybe($maybe, $command)
                    ->next(
                        static fn($finder) => $continuation->stop($finder),
                        static fn($finder) => $continuation->continue($finder),
                    ),
            )
            ->match(
                static fn($command) => self::run($env, $command),
                static fn($usages) => self::displayHelp(
                    $env,
                    true,
                    $usages,
                )
                    ->map(static fn($env) => $env->exit(64)), // EX_USAGE The command was used incorrectly
            );
    }

    /**
     * @return Attempt<Environment>
     */
    private static function run(Environment $env, Command $command): Attempt
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
            return self::displayUsage(
                $env->output(...),
                $bin,
                $usage,
            );
        }

        try {
            $pattern = $usage->pattern();

            [$arguments, $options] = $pattern($arguments);
        } catch (Exception $e) {
            return self::displayUsage(
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
    private static function displayUsage(
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
    private static function displayHelp(
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

        if (\count($lengths) === 0) {
            return Attempt::result($env);
        }

        /** @var positive-int */
        $maxLength = \max($lengths);

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
