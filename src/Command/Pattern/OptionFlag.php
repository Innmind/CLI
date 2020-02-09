<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
    Exception\NoElementMatchingPredicateFound,
};

final class OptionFlag implements Input, Option
{
    private const PATTERN = '~^(?<short>-[a-zA-Z0-9]\|)?(?<name>--[a-zA-Z0-9\-]+)$~';

    private string $name;
    private ?string $short;
    private string $pattern;

    private function __construct(string $name, ?string $short)
    {
        $this->name = $name;
        $this->short = $short;

        if (!is_string($short)) {
            $this->pattern = '~^--'.$name.'$~';
        } else {
            $this->pattern = sprintf(
                '~^-%s|--%s$~',
                $short,
                $this->name
            );
        }
    }

    public static function of(Str $pattern): Input
    {
        if (!$pattern->matches(self::PATTERN)) {
            throw new PatternNotRecognized($pattern->toString());
        }

        $parts = $pattern->capture(self::PATTERN);
        $short = null;

        if ($parts->contains('short') && !$parts->get('short')->empty()) {
            $short = $parts->get('short')->substring(1, -1)->toString();
        }

        return new self(
            $parts->get('name')->substring(2)->toString(),
            $short,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments
    ): Map {
        try {
            $arguments->find(
                fn(string $argument): bool => Str::of($argument)->matches($this->pattern),
            );

            return ($parsed)($this->name, '');
        } catch (NoElementMatchingPredicateFound $e) {
            return $parsed;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clean(Sequence $arguments): Sequence
    {
        return $arguments->filter(function(string $argument): bool {
            return !Str::of($argument)->matches($this->pattern);
        });
    }

    public function toString(): string
    {
        if (!is_string($this->short)) {
            return '--'.$this->name;
        }

        return sprintf('-%s|--%s', $this->short, $this->name);
    }
}
