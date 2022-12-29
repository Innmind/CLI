<?php
declare(strict_types = 1);

namespace Innmind\CLI;

interface Command
{
    public function __invoke(Console $console): Console;

    /**
     * @psalm-mutation-free
     */
    public function usage(): string;
}
