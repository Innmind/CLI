<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row;

use Innmind\CLI\Output\Table\Row as RowInterface;
use Innmind\Immutable\{
    Sequence,
    Str,
};
use function Innmind\Immutable\join;

final class Row implements RowInterface
{
    /** @var Sequence<Cell> */
    private Sequence $cells;

    public function __construct(Cell ...$cells)
    {
        /** @var Sequence<Cell> */
        $this->cells = Sequence::of(Cell::class, ...$cells);
    }

    public function __invoke(string $separator, int ...$widths): string
    {
        $widths = Sequence::ints(...$widths);

        /** @var Sequence<string> */
        $cells = $this
            ->cells
            ->reduce(
                Sequence::strings(),
                static function(Sequence $cells, Cell $cell) use ($widths): Sequence {
                    $cell = Str::of($cell->toString())->rightPad(
                        $widths->get($cells->size())
                    );

                    return $cells->add($cell->toString());
                },
            );

        return join(" $separator ", $cells)
            ->prepend($separator.' ')
            ->append(' '.$separator)
            ->toString();
    }

    public function size(): int
    {
        return $this->cells->size();
    }

    public function width(int $column): int
    {
        return $this->cells->get($column)->width();
    }
}
