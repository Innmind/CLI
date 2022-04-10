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

final class WriteAsASCII implements Writable
{
    private Writable $stream;

    public function __construct(Writable $stream)
    {
        $this->stream = $stream;
    }

    public function write(Str $data): Either
    {
        /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
        return $this->stream->write($data->toEncoding('ASCII'))->map(fn() => $this);
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
