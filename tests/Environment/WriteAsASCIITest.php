<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment\WriteAsASCII,
    Environment\ExitCode,
    Environment,
    Stream\WriteAsASCII as WriteAsASCIIStream,
};
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
};
use PHPUnit\Framework\TestCase;

class WriteAsASCIITest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Environment::class,
            new WriteAsASCII(
                $this->createMock(Environment::class)
            )
        );
    }

    public function testInput()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('input')
            ->willReturn($expected = $this->createMock(Readable::class));

        $this->assertSame($expected, $env->input());
    }

    public function testWrapOutput()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $data = Str::of('');
        $inner
            ->expects($this->once())
            ->method('output')
            ->willReturn($expected = $this->createMock(Writable::class));
        $expected
            ->expects($this->once())
            ->method('write')
            ->with($data->toEncoding('ASCII'));

        $output = $env->output();

        $this->assertInstanceOf(WriteAsASCIIStream::class, $output);
        $this->assertSame($output, $env->output());
        $output->write($data);
    }

    public function testWrapError()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $data = Str::of('');
        $inner
            ->expects($this->once())
            ->method('error')
            ->willReturn($expected = $this->createMock(Writable::class));
        $expected
            ->expects($this->once())
            ->method('write')
            ->with($data->toEncoding('ASCII'));

        $error = $env->error();

        $this->assertInstanceOf(WriteAsASCIIStream::class, $error);
        $this->assertSame($error, $env->error());
        $error->write($data);
    }

    public function testArguments()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('arguments')
            ->willReturn($expected = Sequence::strings());

        $this->assertSame($expected, $env->arguments());
    }

    public function testExit()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('exit')
            ->with(1);

        $this->assertNull($env->exit(1));
    }

    public function testExitCode()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn($expected = new ExitCode(0));

        $this->assertSame($expected, $env->exitCode());
    }

    public function testWorkingDirectory()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn($expected = Path::none());

        $this->assertSame($expected, $env->workingDirectory());
    }

    public function testVariables()
    {
        $env = new WriteAsASCII(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('variables')
            ->willReturn($expected = Map::of('string', 'string'));

        $this->assertSame($expected, $env->variables());
    }
}
