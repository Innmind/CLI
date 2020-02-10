<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\ChoiceQuestion,
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
    Map,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ChoiceQuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new ChoiceQuestion(
            'message',
            Map::of('scalar', 'scalar')
                ('foo', 'bar')
                (1, 'baz')
                (2, 3)
                ('bar', 3)
        );
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
                    return $this->resource ?? $this->resource = tmpfile();
                }

                public function read(int $length = null): Str
                {
                    static $flag = false;

                    if ($flag) {
                        return Str::of("2\n");
                    }

                    $flag = true;

                    return Str::of(' foo,  ');
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
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "message\n";
            }));
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "[foo] bar\n";
            }));
        $output
            ->expects($this->at(2))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "[1] baz\n";
            }));
        $output
            ->expects($this->at(3))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "[2] 3\n";
            }));
        $output
            ->expects($this->at(4))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "[bar] 3\n";
            }));
        $output
            ->expects($this->at(5))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === '> ';
            }));
        $output
            ->expects($this->exactly(6))
            ->method('write');
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

        $this->assertInstanceOf(Map::class, $response);
        $this->assertSame('scalar', (string) $response->keyType());
        $this->assertSame('scalar', (string) $response->valueType());
        $this->assertCount(2, $response);
        $this->assertSame('bar', $response->get('foo'));
        $this->assertSame(3, $response->get(2));
    }

    public function testThrowWhenInvalidValuesKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<scalar, scalar>');

        new ChoiceQuestion(
            'foo',
            Map::of('int', 'scalar')
        );
    }

    public function testThrowWhenInvalidValuesValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<scalar, scalar>');

        new ChoiceQuestion(
            'foo',
            Map::of('scalar', 'int')
        );
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new ChoiceQuestion('watev', Map::of('scalar', 'scalar'));

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
        $question = new ChoiceQuestion('watev', Map::of('scalar', 'scalar'));

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
