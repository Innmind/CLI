<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Output\Table\Row;

use Innmind\CLI\Output\Table\{
    Row\Row,
    Row as RowInterface,
    Row\Cell\Cell,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class RowTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(
                Set\Unicode::strings(),
                Set\Unicode::strings(),
                Set\Unicode::strings()
            )
            ->then(function(string $f, string $s, string $t): void {
                $row = new Row(
                    new Cell($f),
                    new Cell($s),
                    new Cell($t)
                );

                $this->assertInstanceOf(RowInterface::class, $row);
                $this->assertSame(3, $row->size());
                $this->assertSame(mb_strlen($f), $row->width(0));
                $this->assertSame(mb_strlen($s), $row->width(1));
                $this->assertSame(mb_strlen($t), $row->width(2));
                $f = str_pad($f, 10);
                $s = str_pad($s, 12);
                $t = str_pad($t, 14);
                $this->assertSame(
                    "| $f | $s | $t |",
                    $row('|', 10, 12, 14)
                );
            });
    }
}
