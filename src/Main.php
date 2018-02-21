<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Stream,
    Str,
};

abstract class Main
{
    final public function __construct()
    {
        try {
            $this->main($env = new Environment\GlobalEnvironment);
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

    abstract protected function main(Environment $env): void;

    final public function __destruct()
    {
        //main() is the only place to run code
    }

    private function print(string $bin, \Throwable $e, Writable $stream): void
    {
        $traces = Stream::of(
            Str::class,
            Str::of('%s(%s)')->sprintf(get_class($e), $e->getMessage()),
            Str::of('%s:%s')->sprintf($e->getFile(), $e->getLine()),
            Str::of('')
        );
        $traces = Stream::of('array', ...$e->getTrace())
            ->reduce(
                $traces,
                function(Stream $traces, array $trace): Stream {
                    return $traces->add(
                        $this->trace($trace)
                    );
                }
            )
            ->map(static function(Str $trace) use ($bin): Str {
                return $trace
                    ->prepend(': ')
                    ->prepend($bin);
            });
        $stream->write(
            $traces->join("\n")->append("\n")
        );
    }

    private function trace(array $trace): Str
    {
        if (!isset($trace['class'])) {
            return Str::of('%s() at %s:%s')->sprintf(
                $trace['function'],
                $trace['file'],
                $trace['line']
            );
        }

        return Str::of('%s->%s() at %s:%s')->sprintf(
            $trace['class'],
            $trace['function'],
            $trace['file'],
            $trace['line']
        );
    }
}
