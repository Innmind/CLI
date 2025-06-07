<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\Validation\Is;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
    Maybe,
    Identity,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
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
            ->map(static fn($short) => $short->drop(1)->dropEnd(1)->toString())
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
                    ->aggregate(
                        static fn(string|Identity $a, $b) => match (true) {
                            $a instanceof Identity => Sequence::of($a, $b),
                            Str::of('=')->join($parts)->toString() === $a => Sequence::of(Identity::of($b)),
                            default => Sequence::of($a, $b),
                        },
                    )
                    ->toIdentity()
                    ->map(
                        fn($arguments) => $arguments
                            ->find(static fn($value) => $value instanceof Identity)
                            ->keep(Instance::of(Identity::class))
                            ->map(static fn($value): mixed => $value->unwrap())
                            ->keep(Is::string()->asPredicate())
                            ->map(fn($value) => [
                                $arguments->keep(Is::string()->asPredicate()),
                                ($options)($this->name, $value),
                            ])
                            ->match(
                                static fn($found) => $found,
                                fn() => [ // if there is no _next_ argument
                                    $arguments
                                        ->keep(Is::string()->asPredicate())
                                        ->dropEnd(1),
                                    ($options)($this->name, ''), // if there is no next argument then empty string to be coherent with the annotation -{option}={value}
                                ],
                            ),
                    )
                    ->unwrap(),
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
