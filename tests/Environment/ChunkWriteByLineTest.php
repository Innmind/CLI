<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment\ChunkWriteByLine,
    Environment\ExitCode,
    Environment,
    Stream\ChunkWriteByLine as ChunkWriteByLineStream,
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
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ChunkWriteByLineTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Environment::class,
            new ChunkWriteByLine(
                $this->createMock(Environment::class)
            )
        );
    }

    public function testInput()
    {
        $env = new ChunkWriteByLine(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('input')
            ->willReturn($expected = $this->createMock(Readable::class));

        $this->assertSame($expected, $env->input());
    }

    public function testOutput()
    {
        $env = new ChunkWriteByLine(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('output')
            ->willReturn($expected = $this->createMock(Writable::class));

        $this->assertSame($expected, $env->output());
    }

    public function testWrapError()
    {
        $env = new ChunkWriteByLine(
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
            ->with($data);

        $error = $env->error();

        $this->assertInstanceOf(ChunkWriteByLineStream::class, $error);
        $this->assertSame($error, $env->error());
        $error->write($data);
    }

    public function testArguments()
    {
        $env = new ChunkWriteByLine(
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
        $env = new ChunkWriteByLine(
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
        $env = new ChunkWriteByLine(
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
        $env = new ChunkWriteByLine(
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
        $env = new ChunkWriteByLine(
            $inner = $this->createMock(Environment::class)
        );
        $inner
            ->expects($this->once())
            ->method('variables')
            ->willReturn($expected = Map::of('string', 'string'));

        $this->assertSame($expected, $env->variables());
    }

    public function testInteractive()
    {
        $this
            ->forAll(Set\Elements::of(true, false))
            ->then(function($interactive) {
                $env = new ChunkWriteByLine(
                    $inner = $this->createMock(Environment::class),
                );
                $inner
                    ->expects($this->once())
                    ->method('interactive')
                    ->willReturn($interactive);

                $this->assertSame($interactive, $env->interactive());
            });
    }
}
