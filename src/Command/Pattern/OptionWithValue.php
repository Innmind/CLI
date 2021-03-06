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
use function Innmind\Immutable\join;

final class OptionWithValue implements Input, Option
{
    private const PATTERN = '~^(?<short>-[a-zA-Z0-9]\|)?(?<name>--[a-zA-Z0-9\-]+)=$~';

    private string $name;
    private ?string $short;
    private string $pattern;

    private function __construct(string $name, ?string $short)
    {
        $this->name = $name;
        $this->short = $short;

        if (!\is_string($short)) {
            $this->pattern = '~^--'.$name.'=~';
        } else {
            $this->pattern = \sprintf(
                '~^-%s=?|--%s=~',
                $short,
                $this->name,
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

    public function extract(
        Map $parsed,
        int $position,
        Sequence $arguments
    ): Map {
        try {
            $flag = $arguments->find(
                fn(string $argument): bool => Str::of($argument)->matches($this->pattern),
            );
        } catch (NoElementMatchingPredicateFound $e) {
            return $parsed;
        }

        $parts = Str::of($flag)->split('=')->mapTo(
            'string',
            static fn(Str $part): string => $part->toString(),
        );

        if ($parts->size() >= 2) {
            //means it's of the form -{option}={value}
            return ($parsed)(
                $this->name,
                join('=', $parts->drop(1))->toString(), //in case there is an "=" in the value
            );
        }

        //if we're here it's that a short flag with its value as the _next_ argument
        $index = $arguments->indexOf($flag);

        return ($parsed)(
            $this->name,
            $arguments->get($index + 1),
        );
    }

    public function clean(Sequence $arguments): Sequence
    {
        try {
            $flag = $arguments->find(
                fn(string $argument): bool => Str::of($argument)->matches($this->pattern),
            );
        } catch (NoElementMatchingPredicateFound $e) {
            return $arguments;
        }

        $index = $arguments->indexOf($flag);
        $parts = Str::of($flag)->split('=');

        if ($parts->size() >= 2) {
            //means it's of the form -{option}={value}
            return $arguments
                ->take($index)
                ->append($arguments->drop($index + 1));
        }

        //if we're here it's that a short flag with its value as the _next_ argument
        return $arguments
            ->take($index)
            ->append($arguments->drop($index + 2));
    }

    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name.'=';
        }

        return \sprintf('-%s|--%s=', $this->short, $this->name);
    }
}
