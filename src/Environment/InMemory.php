<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\CLI\Environment;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
    Maybe,
};

/**
 * Use this implementation for tests only
 * @psalm-immutable
 */
final class InMemory implements Environment
{
    /** @var Sequence<Str> */
    private Sequence $input;
    /** @var Sequence<Str> */
    private Sequence $output;
    /** @var Sequence<Str> */
    private Sequence $error;
    private bool $interactive;
    /** @var Sequence<string> */
    private Sequence $arguments;
    /** @var Map<string, string> */
    private Map $variables;
    /** @var Maybe<ExitCode> */
    private Maybe $exitCode;
    private Path $workingDirectory;

    /**
     * @param Sequence<Str> $input
     * @param Sequence<Str> $output
     * @param Sequence<Str> $error
     * @param Sequence<string> $arguments
     * @param Map<string, string> $variables
     * @param Maybe<ExitCode> $exitCode
     */
    private function __construct(
        Sequence $input,
        Sequence $output,
        Sequence $error,
        bool $interactive,
        Sequence $arguments,
        Map $variables,
        Maybe $exitCode,
        Path $workingDirectory,
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->error = $error;
        $this->interactive = $interactive;
        $this->arguments = $arguments;
        $this->variables = $variables;
        $this->exitCode = $exitCode;
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @param list<string> $input
     * @param list<string> $arguments
     * @param list<array{string, string}> $variables
     */
    public static function of(
        array $input,
        bool $interactive,
        array $arguments,
        array $variables,
        string $workingDirectory,
    ): self {
        /** @var Maybe<ExitCode> */
        $exitCode = Maybe::nothing();

        return new self(
            Sequence::of(...$input)->map(static fn($string) => Str::of($string, Str\Encoding::ascii)),
            Sequence::of(),
            Sequence::of(),
            $interactive,
            Sequence::of(...$arguments),
            Map::of(...$variables),
            $exitCode,
            Path::of(\rtrim($workingDirectory, '/').'/'),
        );
    }

    public function interactive(): bool
    {
        return $this->interactive;
    }

    public function read(?int $length = null): array
    {
        $data = $this->input->first();
        $input = $this->input->drop(1);

        if (\is_int($length)) {
            // if the read data is longer than the wished length then we re-add
            // the remaining to the start of the input list
            $input = $data
                ->map(static fn($data) => $data->drop($length))
                ->filter(static fn($data) => !$data->empty())
                ->match(
                    static fn($data) => Sequence::of($data)->append($input),
                    static fn() => $input,
                );
            $data = $data->map(static fn($data) => $data->take($length));
        }

        return [$data, new self(
            $input,
            $this->output,
            $this->error,
            $this->interactive,
            $this->arguments,
            $this->variables,
            $this->exitCode,
            $this->workingDirectory,
        )];
    }

    public function output(Str $data): self
    {
        return new self(
            $this->input,
            ($this->output)($data->toEncoding(Str\Encoding::ascii)),
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
            $this->input,
            $this->output,
            ($this->error)($data->toEncoding(Str\Encoding::ascii)),
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

    /**
     * @return list<string>
     */
    public function outputs(): array
    {
        return $this
            ->output
            ->map(static fn($string) => $string->toString())
            ->toList();
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this
            ->error
            ->map(static fn($string) => $string->toString())
            ->toList();
    }
}
