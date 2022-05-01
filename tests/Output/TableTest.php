<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Output;

use Innmind\CLI\{
    Output\Table,
    Output\Table\Row\Row,
    Output\Table\Row\Cell\Cell,
    Exception\EachRowMustBeOfSameSize,
};
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testStringCast()
    {
        $printTo = Table::of(
            null,
            new Row(new Cell('foo'), new Cell('foobar')),
            new Row(new Cell('foobar'), new Cell('foo')),
        );

        $expected = <<<TABLE
+--------+--------+
| foo    | foobar |
| foobar | foo    |
+--------+--------+

TABLE;

        $this->assertSame($expected, $printTo->toString());
    }

    public function testStringCastWithHeader()
    {
        $printTo = Table::of(
            new Row(new Cell('first col'), new Cell('second col')),
            new Row(new Cell('foo'), new Cell('foobar')),
            new Row(new Cell('foobar'), new Cell('foo')),
        );

        $expected = <<<TABLE
+-----------+------------+
| first col | second col |
+-----------+------------+
| foo       | foobar     |
| foobar    | foo        |
+-----------+------------+

TABLE;

        $this->assertSame($expected, $printTo->toString());
    }

    public function testBorderlessTable()
    {
        $printTo = Table::borderless(
            new Row(new Cell('first col'), new Cell('second col')),
            new Row(new Cell('foo'), new Cell('foobar')),
            new Row(new Cell('foobar'), new Cell('foo')),
        );

        $expected = " first col  second col \n".
            " foo        foobar     \n".
            " foobar     foo        \n";

        $this->assertSame($expected, $printTo->toString());
    }

    public function testThrowWhenAllRowsNotOfSameSize()
    {
        $this->expectException(EachRowMustBeOfSameSize::class);

        Table::of(
            null,
            new Row(new Cell('foo'), new Cell('bar')),
            new Row(new Cell('foo')),
        );
    }

    public function testThrowWhenHeaderRowNotOfSameSize()
    {
        $this->expectException(EachRowMustBeOfSameSize::class);

        Table::of(
            new Row(new Cell('foo')),
            new Row(new Cell('foo'), new Cell('bar')),
        );
    }
}
