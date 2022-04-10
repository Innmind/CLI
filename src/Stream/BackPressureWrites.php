<?php
declare(strict_types = 1);

namespace Innmind\CLI\Stream;

use Innmind\Stream\{
    Stream,
    Writable,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\ElapsedPeriod,
    Earth\Period\Millisecond,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Immutable\Str;

final class BackPressureWrites implements Writable
{
    private Writable $stream;
    private Clock $clock;
    private CurrentProcess $process;
    private ElapsedPeriod $threshold;
    private Millisecond $stall;
    private ?PointInTime $lastHit = null;

    public function __construct(
        Writable $stream,
        Clock $clock,
        CurrentProcess $process,
    ) {
        $this->stream = $stream;
        $this->clock = $clock;
        $this->process = $process;
        $this->threshold = new ElapsedPeriod(10); // 10 milliseconds
        $this->stall = new Millisecond(1);
    }

    public function write(Str $data): void
    {
        try {
            if (\is_null($this->lastHit)) {
                return;
            }

            $pressure = $this->clock->now()->elapsedSince($this->lastHit);

            if ($this->threshold->longerThan($pressure)) {
                $this->process->halt($this->stall);
            }
        } finally {
            $this->stream->write($data);
            $this->lastHit = $this->clock->now();
        }
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
