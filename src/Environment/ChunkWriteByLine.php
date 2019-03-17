<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment,
    Stream,
};
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    MapInterface,
    StreamInterface
};

final class ChunkWriteByLine implements Environment
{
    private $environment;
    private $error;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
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
        return $this->error ?? $this->error = new Stream\ChunkWriteByLine(
            $this->environment->error()
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
