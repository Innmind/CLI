<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment,
    Stream,
};
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Sequence,
};

final class ChunkWriteByLine implements Environment
{
    private Environment $environment;
    private ?Writable $error = null;

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
        return $this->error ??= new Stream\ChunkWriteByLine(
            $this->environment->error()
        );
    }

    public function arguments(): Sequence
    {
        return $this->environment->arguments();
    }

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
