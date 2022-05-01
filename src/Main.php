<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\StackTrace\{
    StackTrace,
    Throwable,
    CallFrame,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};

abstract class Main
{
    final public function __construct()
    {
        $os = Factory::build();
        $env = Environment\GlobalEnvironment::of($os->sockets());

        try {
            $env = $this->main($env, $os);
        } catch (\Throwable $e) {
            $env = $this
                ->print($e, $env)
                ->exit(1);
        }

        exit($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => 0,
        ));
    }

    final public function __destruct()
    {
        //main() is the only place to run code
    }

    abstract protected function main(Environment $env, OperatingSystem $os): Environment;

    private function print(\Throwable $e, Environment $env): Environment
    {
        $stack = StackTrace::of($e);

        /** @var Sequence<Str> */
        $chunks = $stack->previous()->reduce(
            $this->renderError($stack->throwable()),
            function(Sequence $lines, Throwable $e): Sequence {
                return $lines
                    ->add(Str::of(''))
                    ->add(Str::of('Caused by'))
                    ->add(Str::of(''))
                    ->append($this->renderError($e));
            },
        );

        return $chunks->reduce(
            $env,
            static fn(Environment $env, Str $line) => $env->error($line->append("\n")),
        );
    }

    /**
     * @return Sequence<Str>
     */
    private function renderError(Throwable $e): Sequence
    {
        $lines = Sequence::of(
            Str::of('%s(%s, %s)')->sprintf(
                $e->class()->toString(),
                $e->message()->toString(),
                (string) $e->code(),
            ),
            Str::of('%s:%s')->sprintf(
                $e->file()->path()->toString(),
                $e->line()->toString(),
            ),
            Str::of(''),
        );

        /** @var Sequence<Str> */
        return $e
            ->callFrames()
            ->reduce(
                $lines,
                function(Sequence $lines, CallFrame $frame): Sequence {
                    return $lines->add($this->renderCallFrame($frame));
                },
            );
    }

    private function renderCallFrame(CallFrame $frame): Str
    {
        $line = Str::of($frame->toString());

        if ($frame instanceof CallFrame\UserLand) {
            $line = $line->append(" at {$frame->file()->path()->toString()}:{$frame->line()->toString()}");
        }

        return $line;
    }
}
