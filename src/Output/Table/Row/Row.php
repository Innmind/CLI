<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row;

use Innmind\CLI\Output\Table\Row as RowInterface;
use Innmind\Immutable\{
    Stream,
    Str,
};

final class Row implements RowInterface
{
    private $cells;

    public function __construct(Cell ...$cells)
    {
        $this->cells = Stream::of(Cell::class, ...$cells);
    }

    public function size(): int
    {
        return $this->cells->size();
    }

    public function width(int $column): int
    {
        return $this->cells->get($column)->width();
    }

    public function __invoke(string $separator, int ...$widths): string
    {
        $widths = Stream::of('int', ...$widths);

        return (string) $this
            ->cells
            ->reduce(
                Stream::of(Str::class),
                static function(Stream $cells, Cell $cell) use ($widths): Stream {
                    $cell = Str::of((string) $cell)->rightPad(
                        $widths->get($cells->size())
                    );

                    return $cells->add($cell);
                }
            )
            ->join(" $separator ")
            ->prepend($separator.' ')
            ->append(' '.$separator);
    }
}
