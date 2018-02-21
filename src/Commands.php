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
    Stream,
};

final class Commands
{
    private $commands;
    private $specifications;

    public function __construct(Command $command, Command ...$commands)
    {
        $this->commands = Set::of(Command::class, $command, ...$commands)->reduce(
            new Map(Specification::class, Command::class),
            static function(Map $commands, Command $command): Map {
                $spec = new Specification($command);

                return $commands->put($spec, $command);
            }
        );
        $this->specifications = $this->commands->reduce(
            new Map('string', Specification::class),
            static function(Map $specs, Specification $spec): Map {
                return $specs->put($spec->name(), $spec);
            }
        );
    }

    public function __invoke(Environment $env): void
    {
        if ($this->commands->size() === 1) {
            $this->run($env, $this->specifications->key());

            return;
        }

        $command = $env->arguments()->get(1); //0 being the tool name

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
            $options = new Options(
                $spec
                    ->pattern()
                    ->options()
                    ->extract($arguments)
            );
            $arguments = $spec->pattern()->options()->clean($arguments);
            $arguments = new Arguments(
                $spec
                    ->pattern()
                    ->arguments()
                    ->extract($arguments)
            );
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
                ->append((string) $description)
        );
    }

    private function displayHelp(Writable $stream): void
    {
        $rows = $this->commands->keys()->reduce(
            Stream::of(Row::class),
            static function(Stream $rows, Specification $spec): Stream {
                return $rows->add(new Row(
                    new Cell($spec->name()),
                    new Cell($spec->shortDescription())
                ));
            }
        );
        $printTo = Table::borderless(null, ...$rows);
        $printTo($stream);
        $stream->write(Str::of("\n"));
    }
}
