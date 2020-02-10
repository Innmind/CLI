<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
};
use Innmind\Stream\{
    Readable,
    Writable,
    Selectable,
    Stream,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\Immutable\Str;
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
                    return $this->resource ?? $this->resource = tmpfile();
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

        $response = $question($env);

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
    }

    public function testAskWithHiddenResponse()
    {
        $question = Question::hiddenResponse('message');

        $this->assertInstanceOf(Question::class, $question);

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
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === 'message ';
            }));
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(static function($line): bool {
                return $line->toString() === "\n";
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

        $response = $question($env);

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
    }
}
