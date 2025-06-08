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
        public string $name,
        public ?string $short,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function named(string $name, ?string $short = null): self
    {
        return new self($name, $short);
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

        $filtered = $arguments->exclude(
            static fn($argument) => Str::of($argument)->matches($pattern),
        );

        if ($filtered->size() !== $arguments->size()) {
            $arguments = $filtered;
            $options = ($options)($this->name, '');
        }

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
