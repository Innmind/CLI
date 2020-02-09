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
        $this->input = new Readable\NonBlocking(
            new Readable\Stream(\STDIN),
        );
        $this->output = new Writable\Stream(\fopen('php://output', 'w'));
        $this->error = new Writable\Stream(\STDERR);
        /** @var list<string> */
        $argv = $_SERVER['argv'];
        $this->arguments = Sequence::strings(...$argv);
        /** @var array<string, string> */
        $variables = \getenv();
        /** @var Map<string, string> */
        $this->variables = Map::of('string', 'string');

        foreach ($variables as $key => $value) {
            $this->variables = ($this->variables)($key, $value);
        }

        $this->exitCode = new ExitCode(0);
        $this->workingDirectory = Path::of(\getcwd().'/');
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
