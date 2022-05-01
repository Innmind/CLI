<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row;

use Innmind\CLI\Output\Table\Row as RowInterface;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Row implements RowInterface
{
    /** @var Sequence<Cell> */
    private Sequence $cells;

    /**
     * @no-named-arguments
     */
    public function __construct(Cell ...$cells)
    {
        $this->cells = Sequence::of(...$cells);
    }

    /**
     * @no-named-arguments
     */
    public function __invoke(string $separator, int ...$widths): string
    {
        $widths = Sequence::ints(...$widths);

        /** @var Sequence<string> */
        $cells = $this
            ->cells
            ->reduce(
                Sequence::strings(),
                static function(Sequence $cells, Cell $cell) use ($widths): Sequence {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    return $widths
                        ->get($cells->size())
                        ->map(static fn($width) => Str::of($cell->toString())->rightPad($width))
                        ->match(
                            static fn($cell) => ($cells)($cell->toString()),
                            static fn() => ($cells)($cell->toString()),
                        );
                },
            );

        return Str::of(" $separator ")
            ->join($cells)
            ->prepend($separator.' ')
            ->append(' '.$separator)
            ->toString();
    }

    public function size(): int
    {
        return $this->cells->size();
    }

    public function widths(): Sequence
    {
        return $this->cells->map(static fn($cell) => $cell->width());
    }
}
