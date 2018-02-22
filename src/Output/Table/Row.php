<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table;

interface Row
{
    public function size(): int;
    public function width(int $column): int;
    public function __invoke(string $separator, int ...$widths): string;
}
