<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\StreamInterface;

interface Option
{
    /**
     * Remove the option from the list of arguments
     *
     * @param StreamInterface<string> $arguments
     *
     * @return StreamInterface<string>
     */
    public function clean(StreamInterface $arguments): StreamInterface;
}
