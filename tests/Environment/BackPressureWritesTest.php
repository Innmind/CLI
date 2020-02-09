<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment\BackPressureWrites,
    Environment\ExitCode,
    Environment,
    Stream\BackPressureWrites as BackPressureWritesStream,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
};
use PHPUnit\Framework\TestCase;

class BackPressureWritesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Environment::class,
            new BackPressureWrites(
                $this->createMock(Environment::class),
                $this->createMock(Clock::class),
                $this->createMock(Halt::class)
            )
        );
    }

    public function testInput()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('input')
            ->willReturn($expected = $this->createMock(Readable::class));

        $this->assertSame($expected, $env->input());
    }

    public function testOutput()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('output')
            ->willReturn($expected = $this->createMock(Writable::class));

        $this->assertSame($expected, $env->output());
    }

    public function testWrapError()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $data = Str::of('');
        $inner
            ->expects($this->once())
            ->method('error')
            ->willReturn($expected = $this->createMock(Writable::class));
        $expected
            ->expects($this->once())
            ->method('write')
            ->with($data);

        $error = $env->error();

        $this->assertInstanceOf(BackPressureWritesStream::class, $error);
        $this->assertSame($error, $env->error());
        $error->write($data);
    }

    public function testArguments()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('arguments')
            ->willReturn($expected = Sequence::strings());

        $this->assertSame($expected, $env->arguments());
    }

    public function testExit()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('exit')
            ->with(1);

        $this->assertNull($env->exit(1));
    }

    public function testExitCode()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn($expected = new ExitCode(0));

        $this->assertSame($expected, $env->exitCode());
    }

    public function testWorkingDirectory()
    {
        $env = new BackPressureWrites(
            $inner = $this->createMock(Environment::class),
            $this->createMock(Clock::class),
            $this->createMock(Halt::class)
        );
        $inner
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn($expected = Path::none());

        $this->assertSame($expected, $env->workingDirectory());
    }
}
