<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment,
    Stream,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\TimeWarp\Halt;
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    MapInterface,
    StreamInterface
};

final class BackPressureWrites implements Environment
{
    private $environment;
    private $clock;
    private $halt;
    private $output;
    private $error;

    public function __construct(
        Environment $environment,
        TimeContinuumInterface $clock,
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
        return $this->output ?? $this->output = new Stream\BackPressureWrites(
            $this->environment->output(),
            $this->clock,
            $this->halt
        );
    }

    public function error(): Writable
    {
        return $this->error ?? $this->error = new Stream\BackPressureWrites(
            $this->environment->error(),
            $this->clock,
            $this->halt
        );
    }

    /**
     * {@inheritdoc}
     */
    public function arguments(): StreamInterface
    {
        return $this->environment->arguments();
    }

    /**
     * {@inheritdoc}
     */
    public function variables(): MapInterface
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

    public function workingDirectory(): PathInterface
    {
        return $this->environment->workingDirectory();
    }
}
