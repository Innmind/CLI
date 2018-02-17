<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Environment\ExitCode;
use Innmind\Stream\{
    Readable,
    Writable
};
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    MapInterface,
    StreamInterface
};

interface Environment
{
    public function input(): Readable;
    public function output(): Writable;
    public function error(): Writable;

    /**
     * @return StreamInterface<string>
     */
    public function arguments(): StreamInterface;

    /**
     * @return MapInterface<string, string>
     */
    public function variables(): MapInterface;
    public function exit(int $code): void;
    public function exitCode(): ExitCode;
    public function workingDirectory(): PathInterface;
}
