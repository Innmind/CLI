<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Environment\ExitCode;
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Sequence
};

interface Environment
{
    public function input(): Readable;
    public function output(): Writable;
    public function error(): Writable;

    /**
     * @return Sequence<string>
     */
    public function arguments(): Sequence;

    /**
     * @return Map<string, string>
     */
    public function variables(): Map;
    public function exit(int $code): void;
    public function exitCode(): ExitCode;
    public function workingDirectory(): Path;
}
