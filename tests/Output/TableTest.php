<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Output;

use Innmind\CLI\{
    Output\Table,
    Output\Table\Row\Row,
    Output\Table\Row\Cell\Cell,
    Exception\EachRowMustBeOfSameSize,
};
use Innmind\Stream\Writable;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testStringCast()
    {
        $printTo = new Table(
            null,
            new Row(new Cell('foo'), new Cell('foobar')),
            new Row(new Cell('foobar'), new Cell('foo'))
        );

        $expected = <<<TABLE
+--------+--------+
| foo    | foobar |
| foobar | foo    |
+--------+--------+
TABLE;

        $this->assertSame($expected, (string) $printTo);
        $output = $this->createMock(Writable::class);
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(static function($str) use ($expected): bool {
                return (string) $str === $expected;
            }));
        $printTo($output);
    }

    public function testStringCastWithHeader()
    {
        $printTo = new Table(
            new Row(new Cell('first col'), new Cell('second col')),
            new Row(new Cell('foo'), new Cell('foobar')),
            new Row(new Cell('foobar'), new Cell('foo'))
        );

        $expected = <<<TABLE
+-----------+------------+
| first col | second col |
+-----------+------------+
| foo       | foobar     |
| foobar    | foo        |
+-----------+------------+
TABLE;

        $this->assertSame($expected, (string) $printTo);
    }

    public function testThrowWhenAllRowsNotOfSameSize()
    {
        $this->expectException(EachRowMustBeOfSameSize::class);

        new Table(
            null,
            new Row(new Cell('foo'), new Cell('bar')),
            new Row(new Cell('foo'))
        );
    }

    public function testThrowWhenHeaderRowNotOfSameSize()
    {
        $this->expectException(EachRowMustBeOfSameSize::class);

        new Table(
            new Row(new Cell('foo')),
            new Row(new Cell('foo'), new Cell('bar'))
        );
    }
}
