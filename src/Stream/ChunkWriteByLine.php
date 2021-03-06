<?php
declare(strict_types = 1);

namespace Innmind\CLI\Stream;

use Innmind\Stream\{
    Stream,
    Writable,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode
};
use Innmind\Immutable\Str;

final class ChunkWriteByLine implements Writable
{
    private Writable $stream;

    public function __construct(Writable $stream)
    {
        $this->stream = $stream;
    }

    public function write(Str $data): void
    {
        $lines = $data->split("\n");
        $lines->dropEnd(1)->foreach(function(Str $line): void {
            $this->stream->write($line->append("\n"));
        });
        $this->stream->write($lines->last());
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    public function seek(Position $position, Mode $mode = null): void
    {
        $this->stream->seek($position, $mode);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function end(): bool
    {
        return $this->stream->end();
    }

    public function size(): Size
    {
        return $this->stream->size();
    }

    public function knowsSize(): bool
    {
        return $this->stream->knowsSize();
    }
}
