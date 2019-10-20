<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    MapInterface,
    Map,
    StreamInterface,
};

final class Arguments
{
    private $arguments;
    private $pack;

    /**
     * @param MapInterface<string, mixed> $arguments
     */
    public function __construct(MapInterface $arguments = null)
    {
        $arguments = $arguments ?? new Map('string', 'mixed');

        if (
            (string) $arguments->keyType() !== 'string' ||
            (string) $arguments->valueType() !== 'mixed'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, mixed>');
        }

        $this->arguments = $arguments;
        $pack = $arguments->values()->filter(static function($argument): bool {
            return $argument instanceof StreamInterface;
        });

        if (!$pack->empty()) {
            $this->pack = $pack->current();
        }
    }

    /**
     * @param StreamInterface<string> $arguments
     */
    public static function of(
        Specification $specification,
        StreamInterface $arguments
    ): self {
        $arguments = $specification->pattern()->options()->clean($arguments);

        return new self(
            $specification
                ->pattern()
                ->arguments()
                ->extract($arguments)
        );
    }

    /**
     * @deprecated
     * @see self::of()
     */
    public static function fromSpecification(
        Specification $specification,
        StreamInterface $arguments
    ): self {
        return self::of($specification, $arguments);
    }

    /**
     * @return string Pack is deprecated
     */
    public function get(string $argument)
    {
        $value =  $this->arguments->get($argument);

        if ($value instanceof StreamInterface) {
            @trigger_error('Use self::pack() instead', E_USER_DEPRECATED);
        }

        return $value;
    }

    public function pack(): StreamInterface
    {
        return $this->pack;
    }

    public function contains(string $argument): bool
    {
        return $this->arguments->contains($argument);
    }
}
