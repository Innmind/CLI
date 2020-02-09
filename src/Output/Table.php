<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output;

use Innmind\CLI\{
    Output\Table\Row,
    Exception\EachRowMustBeOfSameSize,
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
};
use function Innmind\Immutable\{
    unwrap,
    join,
};

final class Table
{
    private ?Row $header;
    private Sequence $rows;
    private string $columnSeparator = '|';
    private string $rowSeparator = '-';
    private string $crossingSeparator = '+';

    public function __construct(?Row $header, Row $row, Row ...$rows)
    {
        $this->header = $header;
        $this->rows = Sequence::of(Row::class, $row, ...$rows);
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
        $stream->write(Str::of($this->toString()));
    }

    public function toString(): string
    {
        $widths = $this->widths($this->rows());
        $rows = $this->rows->reduce(
            Sequence::of(Str::class),
            function(Sequence $rows, Row $row) use ($widths): Sequence {
                return $rows->add(Str::of($row(
                    $this->columnSeparator,
                    ...unwrap($widths),
                )));
            }
        );

        $explodedFirstRow = $rows
            ->first()
            ->split()
            ->map(function(Str $char): Str {
                if ($char->toString() === $this->columnSeparator) {
                    return Str::of($this->crossingSeparator);
                }

                return Str::of($this->rowSeparator);
            })
            ->mapTo(
                'string',
                static fn(Str $char): string => $char->toString(),
            );
        $bound = join('', $explodedFirstRow)->trim();
        $header = Str::of('');
        $rows = join(
            "\n",
            $rows->mapTo(
                'string',
                static fn(Str $row): string => $row->toString(),
            ),
        );


        if ($this->header instanceof Row) {
            $header = Str::of(($this->header)(
                $this->columnSeparator,
                ...unwrap($widths),
            ));

            if (!$bound->empty()) {
                $header = $header
                    ->append("\n")
                    ->append($bound->toString());
            }
        }

        $lines = Sequence::of(Str::class, $bound, $header, $rows, $bound)
            ->filter(static function(Str $line): bool {
                return !$line->empty();
            })
            ->mapTo(
                'string',
                static fn(Str $line): string => $line->toString(),
            );

        return join("\n", $lines)->toString();
    }

    /**
     * @return Sequence<Row>
     */
    private function rows(): Sequence
    {
        if ($this->header instanceof Row) {
            return Sequence::of(Row::class, $this->header, ...unwrap($this->rows));
        }

        return $this->rows;
    }

    /**
     * @param Sequence<Row> $rows
     * @return Sequence<int>
     */
    private function widths(Sequence $rows): Sequence
    {
        $columns = Sequence::of('int', ...range(0, $rows->first()->size() - 1));
        $defaultWidths = $columns->reduce(
            Map::of('int', 'int'),
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
            Sequence::of('int'),
            static function(Sequence $widths, int $column) use ($widthPerColumn): Sequence {
                return $widths->add($widthPerColumn->get($column));
            }
        );
    }
}
