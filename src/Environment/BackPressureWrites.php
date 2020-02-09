<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment,
    Stream,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Sequence,
};

final class BackPressureWrites implements Environment
{
    private Environment $environment;
    private Clock $clock;
    private Halt $halt;
    private ?Writable $error = null;

    public function __construct(
        Environment $environment,
        Clock $clock,
        Halt $halt
    ) {
        $this->environment = $environment;
        $this->clock = $clock;
        $this->halt = $halt;
    }

    public function input(): Readable
    {
        return $this->environment->input();
    }

    public function output(): Writable
    {
        return $this->environment->output();
    }

    public function error(): Writable
    {
        return $this->error ??= new Stream\BackPressureWrites(
            $this->environment->error(),
            $this->clock,
            $this->halt
        );
    }

    /**
     * {@inheritdoc}
     */
    public function arguments(): Sequence
    {
        return $this->environment->arguments();
    }

    /**
     * {@inheritdoc}
     */
    public function variables(): Map
    {
        return $this->environment->variables();
    }

    public function exit(int $code): void
    {
        $this->environment->exit($code);
    }

    public function exitCode(): ExitCode
    {
        return $this->environment->exitCode();
    }

    public function workingDirectory(): Path
    {
        return $this->environment->workingDirectory();
    }
}
