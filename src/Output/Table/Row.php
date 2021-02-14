<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table;

interface Row
{
    public function __invoke(string $separator, int ...$widths): string;
    public function size(): int;
    public function width(int $column): int;
}
