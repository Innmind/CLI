<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\TimeWarp\Halt\Usleep;
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
    StreamInterface,
    Stream,
    Str,
};

abstract class Main
{
    final public function __construct(TimeContinuumInterface $clock = null)
    {
        $os = Factory::build($clock);

        try {
            $this->main(
                $env = new Environment\ChunkWriteByLine(
                    new Environment\BackPressureWrites(
                        new Environment\GlobalEnvironment,
                        $os->clock(),
                        new Usleep
                    )
                ),
                $os
            );
        } catch (\Throwable $e) {
            $this->print(
                $env->arguments()->first(),
                $e,
                $env->error()
            );
            $env->exit(1);
        }

        exit($env->exitCode()->toInt());
    }

    abstract protected function main(Environment $env, OperatingSystem $os): void;

    final public function __destruct()
    {
        //main() is the only place to run code
    }

    private function print(string $bin, \Throwable $e, Writable $stream): void
    {
        $stack = new StackTrace($e);

        $stack
            ->previous()
            ->reduce(
                $this->renderError($stack->throwable()),
                function(StreamInterface $lines, Throwable $e): StreamInterface {
                    return $lines
                        ->add(Str::of(''))
                        ->add(Str::of('Caused by'))
                        ->add(Str::of(''))
                        ->append($this->renderError($e));
                }
            )
            ->map(static function(Str $line) use ($bin): Str {
                return $line->prepend("$bin: ");
            })
            ->reduce(
                $stream,
                static function(Writable $stream, Str $line): Writable {
                    return $stream->write($line->append("\n"));
                }
            );
    }

    /**
     * @return StreamInterface<Str>
     */
    private function renderError(Throwable $e): StreamInterface
    {
        $lines = Stream::of(
            Str::class,
            Str::of('%s(%s, %s)')->sprintf($e->class(), $e->message(), $e->code()),
            Str::of('%s:%s')->sprintf($e->file()->path(), $e->line()),
            Str::of('')
        );

        return $e
            ->callFrames()
            ->reduce(
                $lines,
                function(StreamInterface $lines, CallFrame $frame): StreamInterface {
                    return $lines->add($this->renderCallFrame($frame));
                }
            );
    }

    private function renderCallFrame(CallFrame $frame): Str
    {
        $line = Str::of((string) $frame);

        if ($frame instanceof CallFrame\UserLand) {
            $line = $line->append(" at {$frame->file()->path()}:{$frame->line()}");
        }

        return $line;
    }
}
