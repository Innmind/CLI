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
    Maybe,
    Either,
};
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new Question('message');
        $input = new class implements Readable, Selectable {
            private $resource;

            public function close(): Either
            {
            }

            public function closed(): bool
            {
                return false;
            }

            public function position(): Position
            {
            }

            public function seek(Position $position, Mode $mode = null): Either
            {
            }

            public function rewind(): Either
            {
            }

            public function end(): bool
            {
                return false;
            }

            public function size(): Maybe
            {
                return Maybe::nothing();
            }

            public function resource()
            {
                return $this->resource ?? $this->resource = \tmpfile();
            }

            public function read(int $length = null): Maybe
            {
                static $flag = false;

                if ($flag) {
                    return Maybe::just(Str::of("oo\n"));
                }

                $flag = true;

                return Maybe::just(Str::of('f'));
            }

            public function readLine(): Maybe
            {
                return Maybe::just(Str::of('not used'));
            }

            public function toString(): Maybe
            {
                return Maybe::just('not used');
            }
        };
        $output = $this->createMock(Writable::class);
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === 'message ';
            }))
            ->willReturn(Either::right($output));
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
            ->willReturn(Select::timeoutAfter(new ElapsedPeriod(1000)));

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
