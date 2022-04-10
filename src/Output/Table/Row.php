<?php
declare(strict_types = 1);

namespace Innmind\CLI\Output\Table;

use Innmind\Immutable\Sequence;

interface Row
{
    /**
     * @no-named-arguments
     */
    public function __invoke(string $separator, int ...$widths): string;
    public function size(): int;

    /**
     * @return Sequence<int>
     */
    public function widths(): Sequence;
}
