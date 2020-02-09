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
};
use function Innmind\Immutable\{
    unwrap,
    first,
};

final class Commands
{
    private Map $commands;
    private Map $specifications;

    public function __construct(Command $command, Command ...$commands)
    {
        $this->commands = Set::of(Command::class, $command, ...$commands)->reduce(
            Map::of(Specification::class, Command::class),
            static function(Map $commands, Command $command): Map {
                $spec = new Specification($command);

                return $commands->put($spec, $command);
            }
        );
        $this->specifications = $this->commands->reduce(
            Map::of('string', Specification::class),
            static function(Map $specs, Specification $spec): Map {
                return $specs->put($spec->name(), $spec);
            }
        );
    }

    public function __invoke(Environment $env): void
    {
        if ($this->commands->size() === 1) {
            $this->run(
                $env,
                first($this->specifications->keys()),
            );

            return;
        }

        $arguments = $env->arguments();

        if (!$arguments->indices()->contains(1)) {
            $this->displayHelp($env->error());
            $env->exit(64); //EX_USAGE The command was used incorrectly

            return;
        }

        $command = $arguments->get(1); //0 being the tool name

        if ($command === 'help') {
            $this->displayHelp($env->output());

            return;
        }

        if (!$this->specifications->contains($command)) {
            $this->displayHelp($env->error());
            $env->exit(64); //EX_USAGE The command was used incorrectly

            return;
        }

        $this->run($env, $command);
    }

    private function run(Environment $env, string $command): void
    {
        $spec = $this->specifications->get($command);
        $run = $this->commands->get($spec);
        $arguments = $env->arguments()->drop(1); //drop script name

        if ($arguments->size() > 0 && $arguments->first() === $command) {
            //drop command name, conditional as it can be omitted when only one
            //command defined
            $arguments = $arguments->drop(1);
        }

        if ($arguments->contains('--help')) {
            $this->displayUsage(
                $env->output(),
                $env->arguments()->first(),
                $spec
            );

            return;
        }

        try {
            $options = Options::fromSpecification($spec, $arguments);
            $arguments = Arguments::fromSpecification($spec, $arguments);
        } catch (Exception $e) {
            $this->displayUsage(
                $env->error(),
                $env->arguments()->first(),
                $spec
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
                ->append((string) $spec)
                ->append($description->toString())
                ->append("\n")
        );
    }

    private function displayHelp(Writable $stream): void
    {
        $rows = $this->commands->keys()->reduce(
            Sequence::of(Row::class),
            static function(Sequence $rows, Specification $spec): Sequence {
                return $rows->add(new Row(
                    new Cell($spec->name()),
                    new Cell($spec->shortDescription())
                ));
            }
        );
        $printTo = Table::borderless(null, ...unwrap($rows));
        $printTo($stream);
        $stream->write(Str::of("\n"));
    }
}
