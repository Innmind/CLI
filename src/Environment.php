<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Environment\{
    ExitCode,
    Implementation,
    GlobalEnvironment,
    InMemory,
};
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Attempt,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Environment
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal
     */
    public static function global(IO $io): self
    {
        return new self(GlobalEnvironment::of($io));
    }

    /**
     * @internal
     *
     * @param list<string> $input
     * @param list<string> $arguments
     * @param list<array{string, string}> $variables
     */
    public static function inMemory(
        array $input,
        bool $interactive,
        array $arguments,
        array $variables,
        string $workingDirectory,
    ): self {
        return new self(InMemory::of(
            $input,
            $interactive,
            $arguments,
            $variables,
            $workingDirectory,
        ));
    }

    /**
     * True if the environment running the script is an interactive terminal
     */
    #[\NoDiscard]
    public function interactive(): bool
    {
        return $this->implementation->interactive();
    }

    /**
     * @param ?positive-int $length
     *
     * @return array{Attempt<Str>, self}
     */
    #[\NoDiscard]
    public function read(?int $length = null): array
    {
        [$read, $implementation] = $this->implementation->read($length);

        return [$read, new self($implementation)];
    }

    /**
     * @return Attempt<self>
     */
    #[\NoDiscard]
    public function output(Str $data): Attempt
    {
        return $this
            ->implementation
            ->output($data)
            ->map(static fn($implementation) => new self($implementation));
    }

    /**
     * @return Attempt<self>
     */
    #[\NoDiscard]
    public function error(Str $data): Attempt
    {
        return $this
            ->implementation
            ->error($data)
            ->map(static fn($implementation) => new self($implementation));
    }

    /**
     * @return Sequence<string>
     */
    #[\NoDiscard]
    public function arguments(): Sequence
    {
        return $this->implementation->arguments();
    }

    /**
     * @return Map<string, string>
     */
    #[\NoDiscard]
    public function variables(): Map
    {
        return $this->implementation->variables();
    }

    /**
     * @param int<0, 254> $code
     */
    #[\NoDiscard]
    public function exit(int $code): self
    {
        return new self($this->implementation->exit($code));
    }

    /**
     * @return Maybe<ExitCode>
     */
    #[\NoDiscard]
    public function exitCode(): Maybe
    {
        return $this->implementation->exitCode();
    }

    #[\NoDiscard]
    public function workingDirectory(): Path
    {
        return $this->implementation->workingDirectory();
    }

    /**
     * @internal
     *
     * @return Sequence<array{Str, 'output'|'error'}>
     */
    #[\NoDiscard]
    public function outputted(): Sequence
    {
        return $this->implementation->outputted();
    }
}
