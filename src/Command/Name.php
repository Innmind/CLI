<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Name
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
        private ?string $shortDescription = null,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function shortDescription(): ?string
    {
        return $this->shortDescription;
    }
}
