<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\Environment;
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\{
    PathInterface,
    Path
};
use Innmind\Immutable\{
    StreamInterface,
    Stream,
    MapInterface,
    Map
};

final class GlobalEnvironment implements Environment
{
    private Readable $input;
    private Writable $output;
    private Writable $error;
    private Stream $arguments;
    private Map $variables;
    private ExitCode $exitCode;
    private Path $workingDirectory;

    public function __construct()
    {
        $this->input = new Readable\NonBlocking(
            new Readable\Stream(STDIN)
        );
        $this->output = new Writable\Stream(fopen('php://output', 'w'));
        $this->error = new Writable\Stream(STDERR);
        $this->arguments = Stream::of('string', ...$_SERVER['argv']);
        $variables = getenv();
        $this->variables = Map::of(
            'string',
            'string',
            array_keys($variables),
            array_values($variables)
        );
        $this->exitCode = new ExitCode(0);
        $this->workingDirectory = new Path(getcwd());
    }

    public function input(): Readable
    {
        return $this->input;
    }

    public function output(): Writable
    {
        return $this->output;
    }

    public function error(): Writable
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function arguments(): StreamInterface
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function variables(): MapInterface
    {
        return $this->variables;
    }

    public function exit(int $code): void
    {
        $this->exitCode = new ExitCode($code);
    }

    public function exitCode(): ExitCode
    {
        return $this->exitCode;
    }

    public function workingDirectory(): PathInterface
    {
        return $this->workingDirectory;
    }
}
