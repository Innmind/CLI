<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
    Config,
};
use Innmind\StackTrace\{
    StackTrace,
    Throwable,
    CallFrame,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Attempt,
};

abstract class Main
{
    final public function __construct(?Config $config = null)
    {
        $config ??= Config::new();
        $os = Factory::build($config);
        $env = Environment\GlobalEnvironment::of($config->io());

        try {
            $env = $this->main($env, $os)->unwrap();
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

    /**
     * @return Attempt<Environment>
     */
    abstract protected function main(Environment $env, OperatingSystem $os): Attempt;

    private function print(\Throwable $e, Environment $env): Environment
    {
        $stack = StackTrace::of($e);

        /** @var Sequence<Str> */
        $chunks = $this
            ->renderError($stack->throwable())
            ->append(
                $stack
                    ->previous()
                    ->flatMap(
                        fn($e) => $this
                            ->renderError($e)
                            ->prepend(
                                Sequence::of('', 'Caused by', '')->map(Str::of(...)),
                            ),
                    ),
            );

        return $chunks->reduce(
            $env,
            static fn(Environment $env, Str $line) => $env->error($line->append("\n"))->unwrap(),
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
            ->map($this->renderCallFrame(...))
            ->prepend($lines);
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
