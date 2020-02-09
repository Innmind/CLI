<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row\Cell;

use Innmind\CLI\Output\Table\Row\Cell as CellInterface;
use Innmind\Immutable\Str;

final class Cell implements CellInterface
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function width(): int
    {
        return Str::of($this->value)->length();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
