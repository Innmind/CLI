<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\Environment;
use Innmind\OperatingSystem\Sockets;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class GlobalEnvironment implements Environment
{
    private bool $interactive;
    private Readable\NonBlocking $input;
    /** @var Output<'stdout'> */
    private Output $output;
    /** @var Output<'stderr'> */
    private Output $error;
    private Sockets $sockets;
    /** @var Sequence<string> */
    private Sequence $arguments;
    /** @var Map<string, string> */
    private Map $variables;
    /** @var Maybe<ExitCode> */
    private Maybe $exitCode;
    private Path $workingDirectory;

    /**
     * @param Output<'stdout'> $output
     * @param Output<'stderr'> $error
     * @param Sequence<string> $arguments
     * @param Map<string, string> $variables
     * @param Maybe<ExitCode> $exitCode
     */
    private function __construct(
        Sockets $sockets,
        Readable\NonBlocking $input,
        Output $output,
        Output $error,
        bool $interactive,
        Sequence $arguments,
        Map $variables,
        Maybe $exitCode,
        Path $workingDirectory,
    ) {
        $this->interactive = $interactive;
        $this->input = $input;
        $this->output = $output;
        $this->error = $error;
        $this->sockets = $sockets;
        $this->arguments = $arguments;
        $this->variables = $variables;
        $this->exitCode = $exitCode;
        $this->workingDirectory = $workingDirectory;
    }

    public static function of(Sockets $sockets): self
    {
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         * @var list<string>
         */
        $argv = $_SERVER['argv'];
        $env = \getenv();
        /** @var Map<string, string> */
        $variables = Map::of();

        foreach ($env as $key => $value) {
            $variables = ($variables)($key, $value);
        }

        /** @var Maybe<ExitCode> */
        $exitCode = Maybe::nothing();

        return new self(
            $sockets,
            Readable\NonBlocking::of(
                Readable\Stream::of(\STDIN),
            ),
            Output::stdout(Writable\Stream::of(\fopen('php://output', 'w'))),
            Output::stderr(Writable\Stream::of(\fopen('php://stderr', 'w'))),
            \stream_isatty(\STDIN),
            Sequence::strings(...$argv),
            $variables,
            $exitCode,
            Path::of(\getcwd().'/'),
        );
    }

    public function interactive(): bool
    {
        return $this->interactive;
    }

    public function read(int $length = null): array
    {
        /** @psalm-suppress ImpureMethodCall */
        $watch = $this
            ->sockets
            ->watch(new ElapsedPeriod(60_000)) // one minute
            ->forRead($this->input);

        /**
         * @psalm-suppress ImpureMethodCall
         * @psalm-suppress InvalidArgument
         */
        $data = $watch()
            ->flatMap(fn($ready) => $ready->toRead()->find(
                fn($stream) => $stream === $this->input,
            ))
            ->filter(static fn(Readable $input) => !$input->end())
            ->flatMap(static fn(Readable $input) => $input->read($length))
            ->map(static fn($data) => $data->toEncoding('ASCII'));

        /** @var array{Maybe<Str>, Environment} */
        return [$data, $this];
    }

    public function output(Str $data): self
    {
        return new self(
            $this->sockets,
            $this->input,
            ($this->output)($data),
            $this->error,
            $this->interactive,
            $this->arguments,
            $this->variables,
            $this->exitCode,
            $this->workingDirectory,
        );
    }

    public function error(Str $data): self
    {
        return new self(
            $this->sockets,
            $this->input,
            $this->output,
            ($this->error)($data),
            $this->interactive,
            $this->arguments,
            $this->variables,
            $this->exitCode,
            $this->workingDirectory,
        );
    }

    public function arguments(): Sequence
    {
        return $this->arguments;
    }

    public function variables(): Map
    {
        return $this->variables;
    }

    public function exit(int $code): self
    {
        return new self(
            $this->sockets,
            $this->input,
            $this->output,
            $this->error,
            $this->interactive,
            $this->arguments,
            $this->variables,
            Maybe::just(new ExitCode($code)),
            $this->workingDirectory,
        );
    }

    public function exitCode(): Maybe
    {
        return $this->exitCode;
    }

    public function workingDirectory(): Path
    {
        return $this->workingDirectory;
    }
}
