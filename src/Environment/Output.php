<?php
declare(strict_types = 1);

namespace Innmind\CLI\Environment;

use Innmind\IO\Streams\Stream\Write;
use Innmind\Immutable\{
    Attempt,
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
    /**
     * @param T $kind
     */
    private function __construct(
        private string $kind,
        private Write $stream,
    ) {
    }

    /**
     * @return Attempt<self<T>>
     */
    public function __invoke(Str $data): Attempt
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this
            ->stream
            ->sink(Sequence::of($data))
            ->map(fn() => $this);
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
