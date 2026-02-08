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
    #[\NoDiscard]
    public function __invoke(Console $console): Attempt;

    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function usage(): Usage;
}
