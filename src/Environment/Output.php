<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @template T of 'stdout'|'stderr'
 * @psalm-immutable
 * @internal
 */
final class Output
{
    /** @var T */
    private string $kind;
    private Writable $stream;
    /** @var pure-callable(Writable, Str): Maybe<Writable> */
    private $write;

    /**
     * @param T $kind
     * @param pure-callable(Writable, Str): Maybe<Writable> $write
     */
    private function __construct(
        string $kind,
        Writable $stream,
        callable $write,
    ) {
        $this->kind = $kind;
        $this->stream = $stream;
        $this->write = $write;
    }

    /**
     * @throws \RuntimeException When the stream is no longer writable
     *
     * @return self<T>
     */
    public function __invoke(Str $data): self
    {
        $stream = ($this->write)($this->stream, $data)->match(
            static fn($stream) => $stream,
            fn() => throw new \RuntimeException("Output '{$this->kind}' no longer writable"),
        );

        return new self($this->kind, $stream, $this->write);
    }

    /**
     * @return self<'stdout'>
     */
    public static function stdout(Writable $stream): self
    {
        /** @psalm-suppress InvalidArgument We cheat on the purity here */
        return new self(
            'stdout',
            $stream,
            self::write(...),
        );
    }

    /**
     * @return self<'stderr'>
     */
    public static function stderr(Writable $stream): self
    {
        /** @psalm-suppress InvalidArgument We cheat on the purity here */
        return new self(
            'stderr',
            $stream,
            self::write(...),
        );
    }

    /**
     * @return Maybe<Writable>
     */
    private static function write(Writable $stream, Str $data): Maybe
    {
        /** @var Maybe<Writable> */
        return $stream
            ->write($data->toEncoding('ASCII'))
            ->match(
                static fn($stream) => Maybe::just($stream),
                static fn() => Maybe::nothing(),
            );
    }
}
