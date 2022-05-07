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

    /**
     * @psalm-immutable
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
            ->map(
                static fn($flag) => Str::of($flag)
                    ->split('=')
                    ->map(static fn($part) => $part->toString()),
            )
            ->map(fn($parts) => match ($parts->size()) {
                0 => $parsed, // this case should not happen
                1 => $arguments // this case means the value is in the _next_ argument
                    ->indexOf(Str::of('=')->join($parts)->toString())
                    ->flatMap(static fn($index) => $arguments->get($index + 1))
                    ->match(
                        fn($value) => ($parsed)($this->name, $value),
                        static fn() => $parsed, // if there is no next argument then do not expose the option
                    ),
                default => ($parsed)( // means it's of the form -{option}={value}
                    $this->name,
                    Str::of('=')->join($parts->drop(1))->toString(), // join in case there is an "=" in the value
                ),
            })
            ->match(
                static fn($parsed) => $parsed,
                static fn() => $parsed,
            );
    }

    public function clean(Sequence $arguments): Sequence
    {
        $flag = $arguments->find(
            fn(string $argument): bool => Str::of($argument)->matches($this->pattern),
        );

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @var callable(int, Sequence<string>): Sequence<string>
         */
        $clean = $flag
            ->map(static fn($flag) => Str::of($flag)->split('='))
            ->map(static fn($parts) => match ($parts->size()) {
                0 => static fn(int $_, Sequence $arguments): Sequence => $arguments,
                1 => static fn(int $index, Sequence $arguments): Sequence => $arguments // if we're here it's that a short flag with its value as the _next_ argument
                    ->take($index)
                    ->append($arguments->drop($index + 2)),
                default => static fn(int $index, Sequence $arguments) => $arguments // means it's of the form -{option}={value}
                    ->take($index)
                    ->append($arguments->drop($index + 1)),
            })
            ->match(
                static fn($clean) => $clean,
                static fn() => static fn(int $_, Sequence $arguments) => $arguments,
            );

        return $flag
            ->flatMap(static fn($flag) => $arguments->indexOf($flag))
            ->match(
                static fn($index) => $clean($index, $arguments),
                static fn() => $arguments,
            );
    }

    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name.'=';
        }

        return \sprintf('-%s|--%s=', $this->short, $this->name);
    }
}
