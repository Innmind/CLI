<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\Immutable\{
    Map,
    Sequence,
};
use function Innmind\Immutable\assertMap;

final class Arguments
{
    private Map $arguments;
    private ?Sequence $pack = null;

    /**
     * @param Map<string, mixed> $arguments
     */
    public function __construct(Map $arguments = null)
    {
        $arguments ??= Map::of('string', 'mixed');

        assertMap('string', 'mixed', $arguments, 1);

        $this->arguments = $arguments;
        $pack = $arguments->values()->filter(static function($argument): bool {
            return $argument instanceof Sequence;
        });

        if (!$pack->empty()) {
            $this->pack = $pack->first();
        }
    }

    /**
     * @param Sequence<string> $arguments
     */
    public static function of(
        Specification $specification,
        Sequence $arguments
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
        Sequence $arguments
    ): self {
        return self::of($specification, $arguments);
    }

    /**
     * @return string Pack is deprecated
     */
    public function get(string $argument)
    {
        $value =  $this->arguments->get($argument);

        if ($value instanceof Sequence) {
            @trigger_error('Use self::pack() instead', E_USER_DEPRECATED);
        }

        return $value;
    }

    public function pack(): Sequence
    {
        return $this->pack;
    }

    public function contains(string $argument): bool
    {
        return $this->arguments->contains($argument);
    }
}
