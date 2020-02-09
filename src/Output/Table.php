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
    Sequence,
};

final class Table
{
    private ?Row $header;
    private Stream $rows;
    private string $columnSeparator = '|';
    private string $rowSeparator = '-';
    private string $crossingSeparator = '+';

    public function __construct(?Row $header, Row $row, Row ...$rows)
    {
        $this->header = $header;
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

        if ($header && $header->size() !== $this->rows->first()->size()) {
            throw new EachRowMustBeOfSameSize;
        }
    }

    public static function borderless(?Row $header, Row $row, Row ...$rows): self
    {
        $self = new self($header, $row, ...$rows);
        $self->columnSeparator = '';
        $self->rowSeparator = '';
        $self->crossingSeparator = '';

        return $self;
    }

    public function __invoke(Writable $stream): void
    {
        $stream->write(Str::of((string) $this));
    }

    public function __toString(): string
    {
        $widths = $this->widths($this->rows());
        $rows = $this->rows->reduce(
            Stream::of(Str::class),
            function(Stream $rows, Row $row) use ($widths): Stream {
                return $rows->add(Str::of($row(
                    $this->columnSeparator,
                    ...$widths
                )));
            }
        );

        $bound = $rows
            ->first()
            ->split()
            ->map(function(Str $char): Str {
                if ((string) $char === $this->columnSeparator) {
                    return Str::of($this->crossingSeparator);
                }

                return Str::of($this->rowSeparator);
            })
            ->join('')
            ->trim();
        $header = Str::of('');
        $rows = $rows->join("\n");


        if ($this->header instanceof Row) {
            $header = Str::of(($this->header)(
                $this->columnSeparator,
                ...$widths
            ));

            if (!$bound->empty()) {
                $header = $header
                    ->append("\n")
                    ->append((string) $bound);
            }
        }

        return (string) Sequence::of($bound, $header, $rows, $bound)
            ->filter(static function(Str $line): bool {
                return !$line->empty();
            })
            ->join("\n");
    }

    /**
     * @return Stream<Row>
     */
    private function rows(): Stream
    {
        if ($this->header instanceof Row) {
            return Stream::of(Row::class, $this->header, ...$this->rows);
        }

        return $this->rows;
    }

    /**
     * @param Stream<Row> $rows
     * @return Stream<int>
     */
    private function widths(Stream $rows): Stream
    {
        $columns = Stream::of('int', ...range(0, $rows->first()->size() - 1));
        $defaultWidths = $columns->reduce(
            new Map('int', 'int'),
            static function(Map $widths, int $column): Map {
                return $widths->put($column, 0);
            }
        );
        $widthPerColumn = $rows->reduce(
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

        return $columns->reduce(
            Stream::of('int'),
            static function(Stream $widths, int $column) use ($widthPerColumn): Stream {
                return $widths->add($widthPerColumn->get($column));
            }
        );
    }
}
