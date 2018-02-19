<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Specification,
    Command\Arguments,
    Command\Options,
    Exception\Exception
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Set,
    Map,
    Str,
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
            $this->displayHelp($env);

            return;
        }

        if (!$this->specifications->contains($command)) {
            $this->displayHelp($env);
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
            $this->displayUsage($env, $spec);

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
            $this->displayUsage($env, $spec);
            $env->exit(64); //EX_USAGE The command was used incorrectly

            return;
        }

        $run($env, $arguments, $options);
    }

    private function displayUsage(Environment $env, Specification $spec): void
    {
        $description = Str::of($spec->shortDescription())
            ->append("\n\n")
            ->append($spec->description())
            ->trim();

        if (!$description->empty()) {
            $description = $description->prepend("\n\n");
        }

        $env->output()->write(
            Str::of('usage: ')
                ->append($env->arguments()->first())
                ->append(' ')
                ->append((string) $spec)
                ->append((string) $description)
        );
    }

    private function displayHelp(Environment $env): void
    {
        $this->commands->keys()->reduce(
            $env->output(),
            static function(Writable $output, Specification $spec): Writable {
                return $output->write(
                    Str::of($spec->name())
                        ->append(' ')
                        ->append($spec->shortDescription())
                        ->append("\n")
                );
            }
        );
    }
}
