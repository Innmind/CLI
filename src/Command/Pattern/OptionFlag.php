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
 * @internal
 */
final class OptionFlag implements Input, Option
{
    private const PATTERN = '~^(?<short>-[a-zA-Z0-9]\|)?(?<name>--[a-zA-Z0-9\-]+)$~';

    private function __construct(
        private string $name,
        private ?string $short,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\Override]
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

    #[\Override]
    public function parse(
        Sequence $arguments,
        Map $parsedArguments,
        Sequence $pack,
        Map $options,
    ): array {
        $pattern = \sprintf(
            '~^%s$~',
            $this->toString(),
        );

        $value = $arguments->find(
            static fn($argument) => Str::of($argument)->matches($pattern),
        );
        [$arguments, $options] = $value->match(
            fn() => [
                $arguments->exclude(static fn($argument) => Str::of($argument)->matches($pattern)),
                ($options)($this->name, ''),
            ],
            static fn() => [$arguments, $options],
        );

        return [$arguments, $parsedArguments, $pack, $options];
    }

    #[\Override]
    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name;
        }

        return \sprintf('-%s|--%s', $this->short, $this->name);
    }
}
