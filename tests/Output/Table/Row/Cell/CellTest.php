<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Output\Table\Row\Cell;

use Innmind\CLI\Output\Table\Row\{
    Cell\Cell,
    Cell as CellInterface,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class CellTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this
            ->forAll(Generator\string())
            ->then(function(string $string): void {
                $cell = new Cell($string);

                $this->assertInstanceOf(CellInterface::class, $cell);
                $this->assertSame(mb_strlen($string), $cell->width());
                $this->assertSame($string, $cell->toString());
            });
    }
}
