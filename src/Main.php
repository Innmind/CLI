<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\Stream\Writable;
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
        $env = new Environment\WriteAsASCII(
            new Environment\ChunkWriteByLine(
                new Environment\BackPressureWrites(
                    new Environment\GlobalEnvironment,
                    $os->clock(),
                    $os->process(),
                ),
            ),
        );

        try {
            $this->main($env, $os);
        } catch (\Throwable $e) {
            $this->print($e, $env->error());
            $env->exit(1);
        }

        exit($env->exitCode()->toInt());
    }

    final public function __destruct()
    {
        //main() is the only place to run code
    }

    abstract protected function main(Environment $env, OperatingSystem $os): void;

    private function print(\Throwable $e, Writable $stream): void
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
        $_ = $chunks->foreach(
            static fn(Str $line) => $stream->write($line->append("\n")),
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
