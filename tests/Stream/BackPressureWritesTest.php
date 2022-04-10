<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Stream;

use Innmind\CLI\Stream\BackPressureWrites;
use Innmind\Stream\{
    Writable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\ElapsedPeriod,
    Earth\Period\Millisecond,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class BackPressureWritesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Writable::class,
            new BackPressureWrites(
                $this->createMock(Writable::class),
                $this->createMock(Clock::class),
                $this->createMock(CurrentProcess::class),
            ),
        );
    }

    public function testDoesntWaitOnFirstWrite()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $process = $this->createMock(CurrentProcess::class),
        );
        $data = Str::of('');
        $inner
            ->expects($this->once())
            ->method('write')
            ->with($data);
        $process
            ->expects($this->never())
            ->method('halt');

        $this->assertNull($stream->write($data));
    }

    public function testDoesntWaitWhenLastHitAfter10Milliseconds()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $clock = $this->createMock(Clock::class),
            $process = $this->createMock(CurrentProcess::class),
        );
        $data = Str::of('');
        $inner
            ->expects($this->any())
            ->method('write')
            ->with($data);
        $process
            ->expects($this->never())
            ->method('halt');
        $clock
            ->expects($this->exactly(3))
            ->method('now')
            ->will($this->onConsecutiveCalls(
                $first = $this->createMock(PointInTime::class),
                $second = $this->createMock(PointInTime::class),
                $this->createMock(PointInTime::class),
            ));
        $second
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($first)
            ->willReturn(new ElapsedPeriod(11));

        $stream->write($data);
        $this->assertNull($stream->write($data));
    }

    public function testWaitWhenLastHitInLast10Milliseconds()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $clock = $this->createMock(Clock::class),
            $process = $this->createMock(CurrentProcess::class),
        );
        $data = Str::of('');
        $inner
            ->expects($this->any())
            ->method('write')
            ->with($data);
        $clock
            ->expects($this->exactly(3))
            ->method('now')
            ->will($this->onConsecutiveCalls(
                $first = $this->createMock(PointInTime::class),
                $second = $this->createMock(PointInTime::class),
                $this->createMock(PointInTime::class),
            ));
        $second
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($first)
            ->willReturn(new ElapsedPeriod(9));
        $process
            ->expects($this->once())
            ->method('halt')
            ->with(new Millisecond(1));

        $stream->write($data);
        $this->assertNull($stream->write($data));
    }

    public function testClose()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->once())
            ->method('close');

        $this->assertNull($stream->close());
    }

    public function testClosed()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->exactly(2))
            ->method('closed')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->closed());
        $this->assertFalse($stream->closed());
    }

    public function testPosition()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->once())
            ->method('position')
            ->willReturn($expected = new Position(42));

        $this->assertSame($expected, $stream->position());
    }

    public function testSeek()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $position = new Position(42);
        $mode = Mode::fromStart();
        $inner
            ->expects($this->once())
            ->method('seek')
            ->with($position, $mode);

        $this->assertNull($stream->seek($position, $mode));
    }

    public function testRewind()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->once())
            ->method('rewind');

        $this->assertNull($stream->rewind());
    }

    public function testEnd()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->exactly(2))
            ->method('end')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->end());
        $this->assertFalse($stream->end());
    }

    public function testSize()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->once())
            ->method('size')
            ->willReturn($expected = new Size(42));

        $this->assertSame($expected, $stream->size());
    }

    public function testKnowsSize()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(Clock::class),
            $this->createMock(CurrentProcess::class),
        );
        $inner
            ->expects($this->exactly(2))
            ->method('knowsSize')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->knowsSize());
        $this->assertFalse($stream->knowsSize());
    }
}
