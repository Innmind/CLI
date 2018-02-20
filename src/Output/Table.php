<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output;

use Innmind\CLI\{
    Output\Table\Row,
    Exception\EachRowMustBeOfSameSize,
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Stream,
    Str,
    Map,
};

final class Table
{
    private $rows;

    public function __construct(Row $row, Row ...$rows)
    {
        $this->rows = Stream::of(Row::class, $row, ...$rows);
        $this->rows->drop(1)->reduce(
            $this->rows->first()->size(),
            static function(int $size, Row $row): int {
                if ($row->size() !== $size) {
                    throw new EachRowMustBeOfSameSize;
                }

                return $size;
            }
        );
    }

    public function __invoke(Writable $stream): void
    {
        $stream->write(Str::of((string) $this));
    }

    public function __toString(): string
    {
        $columns = Stream::of('int', ...range(0, $this->rows->first()->size() - 1));
        $defaultWidths = $columns->reduce(
            new Map('int', 'int'),
            static function(Map $widths, int $column): Map {
                return $widths->put($column, 0);
            }
        );
        $widthPerColumn = $this->rows->reduce(
            $defaultWidths,
            static function(Map $widths, Row $row) use ($columns): Map {
                return $columns->reduce(
                    $widths,
                    static function(Map $widths, int $column) use ($row): Map {
                        $width = $row->width($column);

                        if ($width < $widths->get($column)) {
                            return $widths;
                        }

                        return $widths->put($column, $width);
                    }
                );
            }
        );
        $widths = $columns->reduce(
            Stream::of('int'),
            static function(Stream $widths, int $column) use ($widthPerColumn): Stream {
                return $widths->add($widthPerColumn->get($column));
            }
        );
        $rows = $this->rows->reduce(
            Stream::of(Str::class),
            static function(Stream $rows, Row $row) use ($widths): Stream {
                return $rows->add(Str::of($row(...$widths)));
            }
        );

        $bound = $rows
            ->first()
            ->split()
            ->map(static function(Str $char): Str {
                if ((string) $char === '|') {
                    return Str::of('+');
                }

                return Str::of('-');
            })
            ->join('')
            ->append("\n");

        return (string) $rows
            ->join("\n")
            ->append("\n")
            ->prepend((string) $bound)
            ->append((string) $bound)
            ->trim();
    }
}
