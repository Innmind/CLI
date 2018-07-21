<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    StreamInterface,
    MapInterface,
};

final class OptionFlag implements Input, Option
{
    private const PATTERN = '~^(?<short>-[a-zA-Z0-9]\|)?(?<name>--[a-zA-Z0-9\-]+)$~';

    private $name;
    private $short;
    private $pattern;

    private function __construct(string $name, ?string $short)
    {
        $this->name = $name;
        $this->short = $short;

        if (!is_string($short)) {
            $this->pattern = '~^--'.$name.'$~';
        } else {
            $this->pattern = sprintf(
                '~^-%s|--%s$~',
                $this->short,
                $this->name
            );
        }
    }

    public static function fromString(Str $pattern): Input
    {
        if (!$pattern->matches(self::PATTERN)) {
            throw new PatternNotRecognized((string) $pattern);
        }

        $parts = $pattern->capture(self::PATTERN);

        if ($parts->contains('short') && !$parts->get('short')->empty()) {
            $short = (string) $parts->get('short')->substring(1, -1);
        }

        return new self(
            (string) $parts->get('name')->substring(2),
            $short ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function extract(
        MapInterface $parsed,
        int $position,
        StreamInterface $arguments
    ): MapInterface {
        $exists = $arguments->reduce(
            false,
            function(bool $exists, string $argument): bool {
                return $exists || Str::of($argument)->matches($this->pattern);
            }
        );

        if (!$exists) {
            return $parsed;
        }

        return $parsed->put($this->name, true);
    }

    /**
     * {@inheritdoc}
     */
    public function clean(StreamInterface $arguments): StreamInterface
    {
        return $arguments->filter(function(string $argument): bool {
            return !Str::of($argument)->matches($this->pattern);
        });
    }

    public function __toString(): string
    {
        if (!is_string($this->short)) {
            return '--'.$this->name;
        }

        return sprintf('-%s|--%s', $this->short, $this->name);
    }
}
