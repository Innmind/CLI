<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Stream;

use Innmind\CLI\Stream\WriteAsASCII;
use Innmind\Stream\{
    Writable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class WriteAsASCIITest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Writable::class,
            new WriteAsASCII(
                $this->createMock(Writable::class),
            ),
        );
    }

    public function testWrite()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $data = Str::of("foo\nbar\nbaz\n");
        $inner
            ->expects($this->once())
            ->method('write')
            ->with($data->toEncoding('ASCII'))
            ->willReturn(Either::right($inner));

        $this->assertEquals(Either::right($stream), $stream->write($data));
    }

    public function testClose()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $inner
            ->expects($this->once())
            ->method('close')
            ->willReturn($expected = Either::right(new SideEffect));

        $this->assertSame($expected, $stream->close());
    }

    public function testClosed()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
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
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $inner
            ->expects($this->once())
            ->method('position')
            ->willReturn($expected = new Position(42));

        $this->assertSame($expected, $stream->position());
    }

    public function testSeek()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $position = new Position(42);
        $mode = Mode::fromStart;
        $inner
            ->expects($this->once())
            ->method('seek')
            ->with($position, $mode)
            ->willReturn(Either::right($inner));

        $this->assertEquals(
            Either::right($stream),
            $stream->seek($position, $mode),
        );
    }

    public function testRewind()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $inner
            ->expects($this->once())
            ->method('rewind')
            ->willReturn(Either::right($inner));

        $this->assertEquals(Either::right($stream), $stream->rewind());
    }

    public function testEnd()
    {
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
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
        $stream = new WriteAsASCII(
            $inner = $this->createMock(Writable::class),
        );
        $inner
            ->expects($this->once())
            ->method('size')
            ->willReturn($expected = Maybe::just(new Size(42)));

        $this->assertSame($expected, $stream->size());
    }
}
