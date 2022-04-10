<?php
declare(strict_types = 1);

namespace Innmind\CLI\Stream;

use Innmind\Stream\{
    Stream,
    Writable,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
    PositionNotSeekable,
    FailedToWriteToStream,
    DataPartiallyWritten,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class ChunkWriteByLine implements Writable
{
    private Writable $stream;

    public function __construct(Writable $stream)
    {
        $this->stream = $stream;
    }

    public function write(Str $data): Either
    {
        $firstLineWritten = false;
        $map = static function(Str $line) use (&$firstLineWritten): Str {
            if (!$firstLineWritten) {
                $firstLineWritten = true;

                return $line;
            }

            return $line->prepend("\n");
        };

        /**
         * @psalm-suppress MixedArgumentTypeCoercion Due to the reduce
         * @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable>
         */
        return $data
            ->split("\n")
            ->map($map)
            ->reduce(
                Either::right($this->stream),
                static fn(Either $either, $line) => $either->flatMap(
                    static fn(Writable $stream) => $stream->write($line),
                ),
            )
            ->map(fn() => $this);
    }

    public function close(): Either
    {
        return $this->stream->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    public function seek(Position $position, Mode $mode = null): Either
    {
        /** @var Either<PositionNotSeekable, Stream> */
        return $this->stream->seek($position, $mode)->map(fn() => $this);
    }

    public function rewind(): Either
    {
        /** @var Either<PositionNotSeekable, Stream> */
        return $this->stream->rewind()->map(fn() => $this);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->stream->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        return $this->stream->size();
    }
}
