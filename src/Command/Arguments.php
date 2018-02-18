<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\StreamInterface;

final class Arguments
{
    private $arguments;

    /**
     * @param StreamInterface<string> $arguments
     */
    public function __construct(Specification $spec, StreamInterface $arguments)
    {
        $this->arguments = $spec
            ->pattern()
            ->extract($arguments);
    }

    /**
     * @return string|StreamInterface<string>
     */
    public function get(string $argument)
    {
        return $this->arguments->get($argument);
    }

    public function contains(string $argument): bool
    {
        return $this->arguments->contains($argument);
    }
}
