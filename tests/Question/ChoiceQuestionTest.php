<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\Question\ChoiceQuestion;
use Innmind\Stream\{
    Readable,
    Writable,
    Selectable,
    Stream,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\Immutable\{
    Str,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class ChoiceQuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new ChoiceQuestion(
            'message',
            (new Map('scalar', 'scalar'))
                ->put('foo', 'bar')
                ->put(1, 'baz')
                ->put(2, 3)
                ->put('bar', 3)
        );
        $input = new class implements Readable, Selectable {
                private $resource;

                public function close(): Stream
                {
                    return $this;
                }

                public function closed(): bool
                {
                    return false;
                }

                public function position(): Position
                {
                }

                public function seek(Position $position, Mode $mode = null): Stream
                {
                    return $this;
                }

                public function rewind(): Stream
                {
                    return $this;
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

                public function __toString(): string
                {
                    return 'not used';
                }
        };
        $output = $this->createMock(Writable::class);
        $output
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === "message\n";
            }));
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === "[foo] bar\n";
            }));
        $output
            ->expects($this->at(2))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === "[1] baz\n";
            }));
        $output
            ->expects($this->at(3))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === "[2] 3\n";
            }));
        $output
            ->expects($this->at(4))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === "[bar] 3\n";
            }));
        $output
            ->expects($this->at(5))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return (string) $line === '> ';
            }));
        $output
            ->expects($this->exactly(6))
            ->method('write');

        $response = $question($input, $output);

        $this->assertInstanceOf(MapInterface::class, $response);
        $this->assertSame('scalar', (string) $response->keyType());
        $this->assertSame('scalar', (string) $response->valueType());
        $this->assertCount(2, $response);
        $this->assertSame('bar', $response->get('foo'));
        $this->assertSame(3, $response->get(2));
    }

    public function testThrowWhenInvalidValuesKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<scalar, scalar>');

        new ChoiceQuestion(
            'foo',
            new Map('int', 'scalar')
        );
    }

    public function testThrowWhenInvalidValuesValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<scalar, scalar>');

        new ChoiceQuestion(
            'foo',
            new Map('scalar', 'int')
        );
    }
}
