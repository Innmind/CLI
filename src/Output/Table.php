<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output;

use Innmind\CLI\{
    Output\Table\Row,
    Exception\EachRowMustBeOfSameSize,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
};

/**
 * @psalm-immutable
 */
final class Table
{
    private ?Row $header;
    /** @var Sequence<Row> */
    private Sequence $rows;
    private string $columnSeparator = '|';
    private string $rowSeparator = '-';
    private string $crossingSeparator = '+';
    private int $columns;

    /**
     * @no-named-arguments
     */
    private function __construct(?Row $header, Row $row, Row ...$rows)
    {
        $this->columns = $row->size();
        $this->header = $header;
        $this->rows = Sequence::of($row, ...$rows);
        $_ = $this->rows->drop(1)->reduce(
            $row->size(),
            static function(int $size, Row $row): int {
                if ($row->size() !== $size) {
                    throw new EachRowMustBeOfSameSize;
                }

                return $size;
            },
        );

        if ($header && $header->size() !== $row->size()) {
            throw new EachRowMustBeOfSameSize;
        }
    }

    /**
     * @no-named-arguments
     */
    public static function of(?Row $header, Row $row, Row ...$rows): self
    {
        return new self($header, $row, ...$rows);
    }

    /**
     * @no-named-arguments
     */
    public static function borderless(?Row $header, Row $row, Row ...$rows): self
    {
        $self = new self($header, $row, ...$rows);
        $self->columnSeparator = '';
        $self->rowSeparator = '';
        $self->crossingSeparator = '';

        return $self;
    }

    public function toString(): string
    {
        $widths = $this->widths($this->rows());
        $rows = $this->rows->map(
            fn(Row $row): Str => Str::of($row(
                $this->columnSeparator,
                ...$widths->toList(),
            )),
        );

        $explodedFirstRow = $rows
            ->first()
            ->match(
                static fn($row) => $row,
                static fn() => throw new \LogicException('There should be at least one row'),
            )
            ->split()
            ->map(function(Str $char): string {
                if ($char->toString() === $this->columnSeparator) {
                    return $this->crossingSeparator;
                }

                return $this->rowSeparator;
            });
        $bound = Str::of('')->join($explodedFirstRow)->trim();
        $header = Str::of('');
        $rows = Str::of("\n")->join($rows->map(
            static fn(Str $row): string => $row->toString(),
        ));

        if ($this->header instanceof Row) {
            $header = Str::of(($this->header)(
                $this->columnSeparator,
                ...$widths->toList(),
            ));

            if (!$bound->empty()) {
                $header = $header
                    ->append("\n")
                    ->append($bound->toString());
            }
        }

        $lines = Sequence::of($bound, $header, $rows, $bound)
            ->filter(static fn(Str $line): bool => !$line->empty())
            ->map(static fn(Str $line): string => $line->toString());

        return Str::of("\n")->join($lines)->append("\n")->toString();
    }

    /**
     * @return Sequence<Row>
     */
    private function rows(): Sequence
    {
        if ($this->header instanceof Row) {
            return Sequence::of($this->header, ...$this->rows->toList());
        }

        return $this->rows;
    }

    /**
     * @param Sequence<Row> $rows
     * @return Sequence<int>
     */
    private function widths(Sequence $rows): Sequence
    {
        $columns = Sequence::ints(...\range(0, $this->columns - 1));
        $defaultWidths = $columns->map(static fn() => 0);

        /**
         * @psalm-suppress MixedArgument
         * @var Sequence<int>
         */
        return $rows->reduce(
            $defaultWidths,
            fn($widths, $row) => $this->maxWidths($widths, $row->widths()),
        );
    }

    /**
     * @param Sequence<int> $soFar
     * @param Sequence<int> $cells
     *
     * @return Sequence<int>
     */
    private function maxWidths(Sequence $soFar, Sequence $cells): Sequence
    {
        /** @var Sequence<int> */
        return $soFar->reduce(
            Sequence::ints(),
            static fn(Sequence $widths, $width): Sequence => ($widths)(\max(
                $width,
                $cells->get($widths->size())->match(
                    static fn($width) => $width,
                    static fn() => $width, // this case should not happen
                ),
            )),
        );
    }
}
