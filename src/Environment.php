<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Environment\ExitCode;
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
interface Environment
{
    /**
     * True if the environment running the script is an interactive terminal
     */
    public function interactive(): bool;

    /**
     * @param ?positive-int $length
     *
     * @return array{Attempt<Str>, self}
     */
    public function read(?int $length = null): array;
    public function output(Str $data): self;
    public function error(Str $data): self;

    /**
     * @return Sequence<string>
     */
    public function arguments(): Sequence;

    /**
     * @return Map<string, string>
     */
    public function variables(): Map;

    /**
     * @param int<0, 254> $code
     */
    public function exit(int $code): self;

    /**
     * @return Maybe<ExitCode>
     */
    public function exitCode(): Maybe;
    public function workingDirectory(): Path;
}
