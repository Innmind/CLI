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
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod,
    Period\Earth\Millisecond,
};
use Innmind\TimeWarp\Halt;
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
                $this->createMock(TimeContinuumInterface::class),
                $this->createMock(Halt::class)
            )
        );
    }

    public function testDoesntWaitOnFirstWrite()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(TimeContinuumInterface::class),
            $halt = $this->createMock(Halt::class)
        );
        $data = new Str('');
        $inner
            ->expects($this->once())
            ->method('write')
            ->with($data);
        $halt
            ->expects($this->never())
            ->method('__invoke');

        $return = $stream->write($data);

        $this->assertSame($stream, $return);
    }

    public function testDoesntWaitWhenLastHitAfter10Milliseconds()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            $halt = $this->createMock(Halt::class)
        );
        $data = new Str('');
        $inner
            ->expects($this->any())
            ->method('write')
            ->with($data);
        $halt
            ->expects($this->never())
            ->method('__invoke');
        $clock
            ->expects($this->at(0))
            ->method('now')
            ->willReturn($first = $this->createMock(PointInTimeInterface::class));
        $clock
            ->expects($this->at(1))
            ->method('now')
            ->willReturn($second = $this->createMock(PointInTimeInterface::class));
        $second
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($first)
            ->willReturn(new ElapsedPeriod(11));

        $stream->write($data);
        $return = $stream->write($data);

        $this->assertSame($stream, $return);
    }

    public function testWaitWhenLastHitInLast10Milliseconds()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            $halt = $this->createMock(Halt::class)
        );
        $data = new Str('');
        $inner
            ->expects($this->any())
            ->method('write')
            ->with($data);
        $clock
            ->expects($this->at(0))
            ->method('now')
            ->willReturn($first = $this->createMock(PointInTimeInterface::class));
        $clock
            ->expects($this->at(1))
            ->method('now')
            ->willReturn($second = $this->createMock(PointInTimeInterface::class));
        $second
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($first)
            ->willReturn(new ElapsedPeriod(9));
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $clock,
                new Millisecond(1)
            );

        $stream->write($data);
        $return = $stream->write($data);

        $this->assertSame($stream, $return);
    }

    public function testClose()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('close');

        $this->assertSame($stream, $stream->close());
    }

    public function testClosed()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
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
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
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
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
        );
        $position = new Position(42);
        $mode = Mode::fromStart();
        $inner
            ->expects($this->once())
            ->method('seek')
            ->with($position, $mode);

        $this->assertSame($stream, $stream->seek($position, $mode));
    }

    public function testRewind()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('rewind');

        $this->assertSame($stream, $stream->rewind());
    }

    public function testEnd()
    {
        $stream = new BackPressureWrites(
            $inner = $this->createMock(Writable::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
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
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
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
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->exactly(2))
            ->method('knowsSize')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->knowsSize());
        $this->assertFalse($stream->knowsSize());
    }
}
