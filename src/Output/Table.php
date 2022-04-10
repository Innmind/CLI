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
    /** @var Sequence<Row> */
    private Sequence $rows;
    private string $columnSeparator = '|';
    private string $rowSeparator = '-';
    private string $crossingSeparator = '+';

    private function __construct(?Row $header, Row $row, Row ...$rows)
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
            },
        );

        if ($header && $header->size() !== $this->rows->first()->size()) {
            throw new EachRowMustBeOfSameSize;
        }
    }

    public function __invoke(Writable $stream): void
    {
        $stream->write(Str::of($this->toString()));
    }

    public static function of(?Row $header, Row $row, Row ...$rows): self
    {
        return new self($header, $row, ...$rows);
    }

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
        $rows = $this->rows->mapTo(
            Str::class,
            fn(Row $row): Str => Str::of($row(
                $this->columnSeparator,
                ...unwrap($widths),
            )),
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
            ->filter(static fn(Str $line): bool => !$line->empty())
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
        $columns = Sequence::ints(...\range(0, $rows->first()->size() - 1));
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
                $cells->get($widths->size()),
            )),
        );
    }
}
