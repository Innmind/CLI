<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\OperatingSystem\Sockets;
use Innmind\Stream\{
    Readable,
    Writable,
    Selectable,
    Stream,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
    Watch\Select,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new Question('message');
        $input = new class implements Readable, Selectable {
            private $resource;

            public function close(): void
            {
            }

            public function closed(): bool
            {
                return false;
            }

            public function position(): Position
            {
            }

            public function seek(Position $position, Mode $mode = null): void
            {
            }

            public function rewind(): void
            {
            }

            public function end(): bool
            {
                return false;
            }

            public function size(): Size
            {
            }

            public function knowsSize(): bool
            {
                return false;
            }

            public function resource()
            {
                return $this->resource ?? $this->resource = \tmpfile();
            }

            public function read(int $length = null): Str
            {
                static $flag = false;

                if ($flag) {
                    return Str::of("oo\n");
                }

                $flag = true;

                return Str::of('f');
            }

            public function readLine(): Str
            {
                return Str::of('not used');
            }

            public function toString(): string
            {
                return 'not used';
            }
        };
        $output = $this->createMock(Writable::class);
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === 'message ';
            }));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('input')
            ->willReturn($input);
        $env
            ->expects($this->any())
            ->method('output')
            ->willReturn($output);
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(true);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::strings());
        $sockets = $this->createMock(Sockets::class);
        $sockets
            ->expects($this->once())
            ->method('watch')
            ->willReturn(new Select(new ElapsedPeriod(1000)));

        $response = $question($env, $sockets);

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new Question('watev');

        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(false);

        $this->expectException(NonInteractiveTerminal::class);

        $question($env, $this->createMock(Sockets::class));
    }

    public function testThrowWhenOptionToSpecifyNoInteractionIsRequired()
    {
        $question = new Question('watev');

        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(true);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::strings('foo', '--no-interaction', 'bar'));

        $this->expectException(NonInteractiveTerminal::class);

        $question($env, $this->createMock(Sockets::class));
    }
}
