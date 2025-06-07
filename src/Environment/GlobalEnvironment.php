<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\Environment;
use Innmind\TimeContinuum\Period;
use Innmind\IO\{
    IO,
    Streams\Stream\Read,
    Frame,
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
    private Read $input;
    /** @var Output<'stdout'> */
    private Output $output;
    /** @var Output<'stderr'> */
    private Output $error;
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
        Read $input,
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
        $this->arguments = $arguments;
        $this->variables = $variables;
        $this->exitCode = $exitCode;
        $this->workingDirectory = $workingDirectory;
    }

    public static function of(IO $io): self
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
            $io
                ->streams()
                ->acquire(\STDIN)
                ->read()
                ->nonBlocking()
                ->toEncoding(Str\Encoding::ascii)
                ->timeoutAfter(Period::minute(1)),
            Output::stdout(
                $io
                    ->streams()
                    ->acquire(\fopen('php://output', 'w') ?: throw new \RuntimeException('Unable to open stream'))
                    ->write(),
            ),
            Output::stderr(
                $io
                    ->streams()
                    ->acquire(\fopen('php://stderr', 'w') ?: throw new \RuntimeException('Unable to open stream'))
                    ->write(),
            ),
            \stream_isatty(\STDIN),
            Sequence::strings(...$argv),
            $variables,
            $exitCode,
            Path::of((string) \getcwd().'/'),
        );
    }

    #[\Override]
    public function interactive(): bool
    {
        return $this->interactive;
    }

    #[\Override]
    public function read(?int $length = null): array
    {
        /** @psalm-suppress ImpureMethodCall */
        return [
            $this
                ->input
                ->frames(Frame::chunk($length ?? 8192)->loose())
                ->one()
                ->maybe(),
            $this,
        ];
    }

    #[\Override]
    public function output(Str $data): self
    {
        return new self(
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

    #[\Override]
    public function error(Str $data): self
    {
        return new self(
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

    #[\Override]
    public function arguments(): Sequence
    {
        return $this->arguments;
    }

    #[\Override]
    public function variables(): Map
    {
        return $this->variables;
    }

    #[\Override]
    public function exit(int $code): self
    {
        return new self(
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

    #[\Override]
    public function exitCode(): Maybe
    {
        return $this->exitCode;
    }

    #[\Override]
    public function workingDirectory(): Path
    {
        return $this->workingDirectory;
    }
}
