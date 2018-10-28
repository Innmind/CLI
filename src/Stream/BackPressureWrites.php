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
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod,
    Period\Earth\Millisecond,
};
use Innmind\TimeWarp\Halt;
use Innmind\Immutable\Str;

final class BackPressureWrites implements Writable
{
    private $stream;
    private $clock;
    private $halt;
    private $threshold;
    private $stall;
    private $lastHit;

    public function __construct(
        Writable $stream,
        TimeContinuumInterface $clock,
        Halt $halt
    ) {
        $this->stream = $stream;
        $this->clock = $clock;
        $this->halt = $halt;
        $this->threshold = new ElapsedPeriod(10); // 10 milliseconds
        $this->stall = new Millisecond(1);
    }

    public function write(Str $data): Writable
    {
        try {
            if (is_null($this->lastHit)) {
                return $this;
            }

            $pressure = $this->clock->now()->elapsedSince($this->lastHit);

            if ($this->threshold->longerThan($pressure)) {
                ($this->halt)($this->clock, $this->stall);
            }
        } finally {
            $this->stream->write($data);
            $this->lastHit = $this->clock->now();

            return $this;
        }
    }

    public function close(): Stream
    {
        $this->stream->close();

        return $this;
    }

    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    public function seek(Position $position, Mode $mode = null): Stream
    {
        $this->stream->seek($position, $mode);

        return $this;
    }

    public function rewind(): Stream
    {
        $this->stream->rewind();

        return $this;
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
