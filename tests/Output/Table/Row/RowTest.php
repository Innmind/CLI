<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Output\Table\Row;

use Innmind\CLI\Output\Table\{
    Row\Row,
    Row as RowInterface,
    Row\Cell\Cell,
};
use function Innmind\Immutable\unwrap;
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
                Set\Unicode::strings(),
            )
            ->then(function(string $f, string $s, string $t): void {
                $row = new Row(
                    new Cell($f),
                    new Cell($s),
                    new Cell($t),
                );

                $this->assertInstanceOf(RowInterface::class, $row);
                $this->assertSame(3, $row->size());
                $this->assertSame(
                    [\mb_strlen($f), \mb_strlen($s), \mb_strlen($t)],
                    unwrap($row->widths()),
                );
                $f = \str_pad($f, 10);
                $s = \str_pad($s, 12);
                $t = \str_pad($t, 14);
                $this->assertSame(
                    "| $f | $s | $t |",
                    $row('|', 10, 12, 14),
                );
            });
    }
}
