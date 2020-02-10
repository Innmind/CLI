<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row;

interface Cell
{
    public function width(): int;
    public function toString(): string;
}
