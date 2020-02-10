<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\Sequence;

interface Option
{
    /**
     * Remove the option from the list of arguments
     *
     * @param Sequence<string> $arguments
     *
     * @return Sequence<string>
     */
    public function clean(Sequence $arguments): Sequence;
}
