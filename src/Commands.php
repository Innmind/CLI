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
    Set,
    Map,
    Str,
    Sequence,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\{
    unwrap,
    first,
};

final class Commands
{
    /** @var Map<Specification, Command> */
    private Map $commands;
    /** @var Set<Specification> */
    private Set $specifications;

    public function __construct(Command $command, Command ...$commands)
    {
        $this->commands = Set::of(Command::class, $command, ...$commands)->toMapOf(
            Specification::class,
            Command::class,
            static function(Command $command): \Generator {
                yield new Specification($command) => $command;
            },
        );
        $this->specifications = $this->commands->keys();
    }

    public function __invoke(Environment $env): void
    {
        if ($this->commands->size() === 1) {
            $this->run(
                $env,
                first($this->specifications),
            );

            return;
        }

        $arguments = $env->arguments();

        if (!$arguments->indices()->contains(1)) {
            $this->displayHelp(
                $env->error(),
                $this->specifications,
            );
            $env->exit(64); //EX_USAGE The command was used incorrectly

            return;
        }

        $command = $arguments->get(1); //0 being the tool name

        if ($command === 'help') {
            $this->displayHelp(
                $env->output(),
                $this->specifications,
            );

            return;
        }

        try {
            $specification = $this->specifications->find(
                static fn(Specification $spec): bool => $spec->is($command),
            );
            $this->run($env, $specification);

            return;
        } catch (NoElementMatchingPredicateFound $e) {
            // attempt pattern matching
        }

        $specifications = $this->specifications->filter(
            static fn(Specification $spec): bool => $spec->matches($command),
        );

        if ($specifications->size() === 1) {
            $this->run($env, first($specifications));

            return;
        }

        $this->displayHelp(
            $env->error(),
            $specifications->empty() ? $this->specifications : $specifications,
        );
        $env->exit(64); //EX_USAGE The command was used incorrectly
    }

    private function run(Environment $env, Specification $spec): void
    {
        $run = $this->commands->get($spec);
        $arguments = $env->arguments()->drop(1); //drop script name

        if (!$arguments->empty() && $spec->matches($arguments->first())) {
            //drop command name, conditional as it can be omitted when only one
            //command defined
            $arguments = $arguments->drop(1);
        }

        if ($arguments->contains('--help')) {
            $this->displayUsage(
                $env->output(),
                $env->arguments()->first(),
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
                $env->arguments()->first(),
                $spec,
            );
            $env->exit(64); //EX_USAGE The command was used incorrectly

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
     * @param Set<Specification> $specifications
     */
    private function displayHelp(
        Writable $stream,
        Set $specifications,
    ): void {
        $rows = $specifications->toSequenceOf(
            Row::class,
            static fn(Specification $spec): \Generator => yield new Row(
                new Cell($spec->name()),
                new Cell($spec->shortDescription()),
            ),
        );
        $printTo = Table::borderless(null, ...unwrap($rows));
        $printTo($stream);
        $stream->write(Str::of("\n"));
    }
}
