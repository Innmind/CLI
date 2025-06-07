<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\IO\Streams\Stream\Write;
use Innmind\Immutable\{
    Str,
    Sequence,
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
    private Write $stream;

    /**
     * @param T $kind
     */
    private function __construct(
        string $kind,
        Write $stream,
    ) {
        $this->kind = $kind;
        $this->stream = $stream;
    }

    /**
     * @throws \Throwable When the stream is no longer writable
     *
     * @return self<T>
     */
    public function __invoke(Str $data): self
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->stream
            ->sink(Sequence::of($data))
            ->map(fn() => $this)
            ->unwrap();
    }

    /**
     * @return self<'stdout'>
     */
    public static function stdout(Write $stream): self
    {
        return new self(
            'stdout',
            $stream,
        );
    }

    /**
     * @return self<'stderr'>
     */
    public static function stderr(Write $stream): self
    {
        return new self(
            'stderr',
            $stream,
        );
    }
}
