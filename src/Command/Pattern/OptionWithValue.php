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

    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array {
        $value = $arguments->find(
            fn($argument) => Str::of($argument)->matches($this->pattern),
        );
        /** @psalm-suppress ArgumentTypeCoercion */
        [$arguments, $options] = $value
            ->map(
                static fn($flag) => Str::of($flag)
                    ->split('=')
                    ->map(static fn($part) => $part->toString()),
            )
            ->map(fn($parts) => match ($parts->size()) {
                0 => [$arguments, $options], // this case should not happen
                1 => $arguments // this case means the value is in the _next_ argument
                    ->indexOf(Str::of('=')->join($parts)->toString())
                    ->map(
                        fn($index) => $arguments
                            ->get($index + 1)
                            ->map(fn($value) => [
                                $arguments->take($index)->append($arguments->drop($index + 2)),
                                ($options)($this->name, $value),
                            ])
                            ->match(
                                static fn($found) => $found,
                                fn() => [ // if there is no _next_ argument
                                    $arguments->take($index),
                                    ($options)($this->name, ''), // if there is no next argument then empty string to be coherent with the annotation -{option}={value}
                                ],
                            ),
                    )
                    ->match(
                        static fn($found) => $found,
                        static fn() => [$arguments, $options], // this case should not happen
                    ),
                default => [ // means it's of the form -{option}={value}
                    $arguments->filter(
                        fn($argument) => !Str::of($argument)->matches($this->pattern),
                    ),
                    ($options)(
                        $this->name,
                        Str::of('=')->join($parts->drop(1))->toString(), // join in case there is an "=" in the value
                    ),
                ],
            })
            ->match(
                static fn($found) => $found,
                static fn() => [$arguments, $options],
            );

        return [$arguments, $parsedArguments, $pack, $options];
    }

    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name.'=';
        }

        return \sprintf('-%s|--%s=', $this->short, $this->name);
    }
}
