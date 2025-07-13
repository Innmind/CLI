<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Command\Usage;
use Innmind\Immutable\Attempt;

interface Command
{
    /**
     * @return Attempt<Console>
     */
    public function __invoke(Console $console): Attempt;

    /**
     * @psalm-mutation-free
     */
    public function usage(): Usage;
}
