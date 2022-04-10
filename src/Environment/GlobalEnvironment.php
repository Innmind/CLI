<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\Environment;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
};

final class GlobalEnvironment implements Environment
{
    private bool $interactive;
    private Readable $input;
    private Writable $output;
    private Writable $error;
    /** @var Sequence<string> */
    private Sequence $arguments;
    /** @var Map<string, string> */
    private Map $variables;
    private ExitCode $exitCode;
    private Path $workingDirectory;

    public function __construct()
    {
        $this->interactive = \stream_isatty(\STDIN);
        $this->input = Readable\NonBlocking::of(
            Readable\Stream::of(\STDIN),
        );
        $this->output = Writable\Stream::of(\fopen('php://output', 'w'));
        $this->error = Writable\Stream::of(\STDERR);
        /** @var list<string> */
        $argv = $_SERVER['argv'];
        $this->arguments = Sequence::strings(...$argv);
        $variables = \getenv();
        /** @var Map<string, string> */
        $this->variables = Map::of();

        foreach ($variables as $key => $value) {
            $this->variables = ($this->variables)($key, $value);
        }

        $this->exitCode = new ExitCode(0);
        $this->workingDirectory = Path::of(\getcwd().'/');
    }

    public function interactive(): bool
    {
        return $this->interactive;
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

    public function arguments(): Sequence
    {
        return $this->arguments;
    }

    public function variables(): Map
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

    public function workingDirectory(): Path
    {
        return $this->workingDirectory;
    }
}
