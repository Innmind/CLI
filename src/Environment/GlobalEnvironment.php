<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

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
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class GlobalEnvironment implements Implementation
{
    /**
     * @param Output<'stdout'> $output
     * @param Output<'stderr'> $error
     * @param Sequence<string> $arguments
     * @param Map<string, string> $variables
     * @param Maybe<ExitCode> $exitCode
     */
    private function __construct(
        private Read $input,
        private Output $output,
        private Output $error,
        private bool $interactive,
        private Sequence $arguments,
        private Map $variables,
        private Maybe $exitCode,
        private Path $workingDirectory,
    ) {
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
                ->one(),
            $this,
        ];
    }

    #[\Override]
    public function output(Str $data): Attempt
    {
        return ($this->output)($data)->map(
            fn($output) => new self(
                $this->input,
                $output,
                $this->error,
                $this->interactive,
                $this->arguments,
                $this->variables,
                $this->exitCode,
                $this->workingDirectory,
            ),
        );
    }

    #[\Override]
    public function error(Str $data): Attempt
    {
        return ($this->error)($data)->map(
            fn($error) => new self(
                $this->input,
                $this->output,
                $error,
                $this->interactive,
                $this->arguments,
                $this->variables,
                $this->exitCode,
                $this->workingDirectory,
            ),
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

    #[\Override]
    public function outputted(): Sequence
    {
        return Sequence::of();
    }
}
