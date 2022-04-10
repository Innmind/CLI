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
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\ElapsedPeriod,
    Earth\Period\Millisecond,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

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

    public function write(Str $data): Either
    {
        if (!\is_null($this->lastHit)) {
            $pressure = $this->clock->now()->elapsedSince($this->lastHit);

            if ($this->threshold->longerThan($pressure)) {
                $this->process->halt($this->stall);
            }
        }

        $this->lastHit = $this->clock->now();

        /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
        return $this->stream->write($data)->map(fn() => $this);
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
