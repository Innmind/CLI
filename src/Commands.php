<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Specification,
    Command\Arguments,
    Command\Options,
    Exception\Exception,
    Output\Table,
    Output\Table\Row\Row,
    Output\Table\Row\Cell\Cell,
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
};

final class Commands
{
    /** @var Map<Specification, Command> */
    private Map $commands;
    /** @var Sequence<Specification> */
    private Sequence $specifications;

    public function __construct(Command $command, Command ...$commands)
    {
        $commands = Sequence::of($command, ...$commands)->map(
            static fn($command) => [new Specification($command), $command],
        );
        $this->commands = Map::of(...$commands->toList());
        $this->specifications = $commands->map(static fn($command) => $command[0]);
    }

    public function __invoke(Environment $env): void
    {
        if ($this->commands->size() === 1) {
            $_ = $this
                ->specifications
                ->find(static fn() => true) // first
                ->match(
                    fn($specification) => $this->run($env, $specification),
                    static fn() => null,
                );

            return;
        }

        $command = $env
            ->arguments()
            ->get(1) // 0 being the tool name
            ->match(
                static fn($command) => $command,
                static fn() => null,
            );

        if (!\is_string($command)) {
            $this->displayHelp(
                $env->error(),
                $this->specifications,
            );
            $env->exit(64); // EX_USAGE The command was used incorrectly

            return;
        }

        if ($command === 'help') {
            $this->displayHelp(
                $env->output(),
                $this->specifications,
            );

            return;
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

        $_ = $specifications->match(
            fn($spec, $rest) => match ($rest->empty()) {
                true => $this->run($env, $spec),
                false => $this->displayHelp(
                    $env->error(),
                    Sequence::of($spec)->append($rest),
                    static fn() => $env->exit(64), // EX_USAGE The command was used incorrectly
                ),
            },
            fn() => $this->displayHelp(
                $env->error(),
                $this->specifications,
                static fn() => $env->exit(64), // EX_USAGE The command was used incorrectly
            ),
        );
    }

    private function run(Environment $env, Specification $spec): void
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
            $this->displayUsage(
                $env->output(),
                $bin,
                $spec,
            );

            return;
        }

        try {
            $options = Options::of($spec, $arguments);
            $arguments = Arguments::of($spec, $arguments);
        } catch (Exception $e) {
            $this->displayUsage(
                $env->error(),
                $bin,
                $spec,
            );
            $env->exit(64); // EX_USAGE The command was used incorrectly

            return;
        }

        $run($env, $arguments, $options);
    }

    private function displayUsage(Writable $stream, string $bin, Specification $spec): void
    {
        $description = Str::of($spec->shortDescription())
            ->append("\n\n")
            ->append($spec->description())
            ->trim();

        if (!$description->empty()) {
            $description = $description->prepend("\n\n");
        }

        $stream->write(
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
     */
    private function displayHelp(
        Writable $stream,
        Sequence $specifications,
        callable $exit = null,
    ): void {
        $rows = $specifications->map(
            static fn(Specification $spec) => new Row(
                new Cell($spec->name()),
                new Cell($spec->shortDescription()),
            ),
        );
        $printTo = Table::borderless(null, ...$rows->toList());
        $printTo($stream);
        $stream->write(Str::of("\n"));

        if ($exit) {
            $exit();
        }
    }
}
