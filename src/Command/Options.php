<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\StreamInterface;

final class Options
{
    private $options;

    /**
     * @param StreamInterface<string> $arguments
     */
    public function __construct(Specification $spec, StreamInterface $arguments)
    {
        $this->options = $spec
            ->pattern()
            ->options()
            ->extract($arguments);
    }

    /**
     * @return string|bool
     */
    public function get(string $argument)
    {
        return $this->options->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->options->contains($argument);
    }
}
