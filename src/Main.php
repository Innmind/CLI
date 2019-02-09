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
use Innmind\Immutable\{
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
                $env = new Environment\BackPressureWrites(
                    new Environment\GlobalEnvironment,
                    $os->clock(),
                    new Usleep
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

        if (!isset($trace['file'])) {
            return Str::of('%s%s%s()')->sprintf(
                $trace['class'],
                $trace['type'],
                $trace['function']
            );
        }

        return Str::of('%s%s%s() at %s:%s')->sprintf(
            $trace['class'],
            $trace['type'],
            $trace['function'],
            $trace['file'],
            $trace['line']
        );
    }
}
