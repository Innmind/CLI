<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table\Row;

/**
 * @psalm-immutable
 */
interface Cell
{
    public function width(): int;
    public function toString(): string;
}
