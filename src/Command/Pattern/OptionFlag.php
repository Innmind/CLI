<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
    Maybe,
};

/**
 * @psalm-immutable
 */
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

        if (!\is_string($short)) {
            $this->pattern = '~^--'.$name.'$~';
        } else {
            $this->pattern = \sprintf(
                '~^-%s|--%s$~',
                $short,
                $this->name,
            );
        }
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $pattern): Maybe
    {
        $parts = $pattern->capture(self::PATTERN);
        $short = $parts
            ->get('short')
            ->filter(static fn($short) => !$short->empty())
            ->map(static fn($short) => $short->substring(1, -1)->toString())
            ->match(
                static fn($short) => $short,
                static fn() => null,
            );

        /** @var Maybe<Input> */
        return $parts
            ->get('name')
            ->map(static fn($name) => $name->drop(2)->toString())
            ->map(static fn($name) => new self($name, $short));
    }

    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments,
    ): Map {
        return $arguments
            ->find(
                fn(string $argument): bool => Str::of($argument)->matches($this->pattern),
            )
            ->match(
                fn() => ($parsed)($this->name, ''),
                static fn() => $parsed,
            );
    }

    public function clean(Sequence $arguments): Sequence
    {
        return $arguments->filter(function(string $argument): bool {
            return !Str::of($argument)->matches($this->pattern);
        });
    }

    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name;
        }

        return \sprintf('-%s|--%s', $this->short, $this->name);
    }
}
