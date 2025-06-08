<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Specification,
    Exception\Exception,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
    Attempt,
};

final class Commands
{
    /** @var Map<Specification, Command> */
    private Map $commands;
    /** @var Sequence<Specification> */
    private Sequence $specifications;

    private function __construct(Command $command, Command ...$commands)
    {
        $commands = Sequence::of($command, ...$commands)->map(
            static fn($command) => [new Specification($command), $command],
        );
        $this->commands = Map::of(...$commands->toList());
        $this->specifications = $commands->map(static fn($command) => $command[0]);
    }

    /**
     * @return Attempt<Environment>
     */
    public function __invoke(Environment $env): Attempt
    {
        if ($this->commands->size() === 1) {
            return $this
                ->specifications
                ->first()
                ->match(
                    fn($specification) => $this->run($env, $specification),
                    static fn() => Attempt::result($env),
                );
        }

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
                    $this->specifications,
                )
                ->map(static fn($env) => $env->exit(64)); // EX_USAGE The command was used incorrectly
        }

        if ($command === 'help') {
            return $this->displayHelp(
                $env,
                false,
                $this->specifications,
            );
        }

        $specifications = $this
            ->specifications
            ->find(static fn($spec) => $spec->is($command))
            ->map(static fn($spec) => Sequence::of($spec))
            ->match(
                static fn($specifications) => $specifications,
                fn() => $this->specifications->filter(
                    static fn($spec) => $spec->matches($command),
                ),
            );

        return $specifications->match(
            fn($spec, $rest) => match ($rest->empty()) {
                true => $this->run($env, $spec),
                false => $this
                    ->displayHelp(
                        $env,
                        true,
                        Sequence::of($spec)->append($rest),
                    )
                    ->map(static fn($env) => $env->exit(64)), // EX_USAGE The command was used incorrectly
            },
            fn() => $this
                ->displayHelp(
                    $env,
                    true,
                    $this->specifications,
                )
                ->map(static fn($env) => $env->exit(64)), // EX_USAGE The command was used incorrectly
        );
    }

    public static function of(Command $command, Command ...$commands): self
    {
        return new self($command, ...$commands);
    }

    /**
     * @return Attempt<Environment>
     */
    private function run(Environment $env, Specification $spec): Attempt
    {
        $run = $this->commands->get($spec)->match(
            static fn($command) => $command,
            static fn() => throw new \LogicException('This case should not be possible'),
        );
        [$bin, $arguments] = $env->arguments()->match(
            static fn($bin, $arguments) => [$bin, $arguments],
            static fn() => throw new \LogicException('Arguments list should not be empty'),
        );

        // drop command name, conditional as it can be omitted when only one
        // command defined
        $arguments = $arguments
            ->first()
            ->filter(static fn($first) => $spec->matches($first))
            ->match(
                static fn() => $arguments->drop(1),
                static fn() => $arguments,
            );

        if ($arguments->contains('--help')) {
            return $this->displayUsage(
                $env->output(...),
                $bin,
                $spec,
            );
        }

        try {
            $pattern = $spec->pattern();

            [$arguments, $options] = $pattern($arguments);
        } catch (Exception $e) {
            return $this
                ->displayUsage(
                    $env->error(...),
                    $bin,
                    $spec,
                )
                ->map(static fn($env) => $env->exit(64)); // EX_USAGE The command was used incorrectly
        }

        return $run(Console::of($env, $arguments, $options))->map(
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
        Specification $spec,
    ): Attempt {
        $description = Str::of($spec->shortDescription())
            ->append("\n\n")
            ->append($spec->description())
            ->trim();

        if (!$description->empty()) {
            $description = $description->prepend("\n\n");
        }

        return $write(
            Str::of('usage: ')
                ->append($bin)
                ->append(' ')
                ->append($spec->toString())
                ->append($description->toString())
                ->append("\n"),
        );
    }

    /**
     * @param Sequence<Specification> $specifications
     *
     * @return Attempt<Environment>
     */
    private function displayHelp(
        Environment $env,
        bool $error,
        Sequence $specifications,
    ): Attempt {
        $names = $specifications->map(
            static fn($spec) => Str::of($spec->name()),
        );
        $lengths = $names
            ->map(static fn($name) => $name->length())
            ->toList();
        /** @var positive-int */
        $maxLength = \max(...$lengths);

        $rows = $specifications->map(
            static fn($spec) => Str::of(' ')
                ->append(Str::of($spec->name())->rightPad($maxLength)->toString())
                ->append('  ')
                ->append($spec->shortDescription())
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
