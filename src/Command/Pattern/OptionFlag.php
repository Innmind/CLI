<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Command\Usage;
use Innmind\Validation\Is;
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
final class OptionFlag implements Input
{
    private const PATTERN = '~^(?<short>-[a-zA-Z0-9]\|)?(?<name>--[a-zA-Z0-9\-]+)$~';

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $short
     */
    private function __construct(
        private string $name,
        private ?string $short,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param ?non-empty-string $short
     */
    public static function named(string $name, ?string $short = null): self
    {
        return new self($name, $short);
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function walk(Usage $usage, Str $pattern): Maybe
    {
        return self::of($pattern)->map(
            static fn($self) => $usage->flag($self->name, $self->short),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Str $pattern): Maybe
    {
        $parts = $pattern->capture(self::PATTERN);
        $short = $parts
            ->get('short')
            ->filter(static fn($short) => !$short->empty())
            ->map(static fn($short) => $short->drop(1)->dropEnd(1)->toString())
            ->keep(Is::string()->nonEmpty()->asPredicate())
            ->match(
                static fn($short) => $short,
                static fn() => null,
            );

        return $parts
            ->get('name')
            ->map(static fn($name) => $name->drop(2)->toString())
            ->keep(Is::string()->nonEmpty()->asPredicate())
            ->map(static fn($name) => new self($name, $short));
    }

    /**
     * @param Sequence<string> $arguments
     * @param Map<string, string> $options
     *
     * @return array{
     *     Sequence<string>,
     *     Map<string, string>,
     * }
     */
    public function parse(
        Sequence $arguments,
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

        return [$arguments, $options];
    }

    public function toString(): string
    {
        if (!\is_string($this->short)) {
            return '--'.$this->name;
        }

        return \sprintf('-%s|--%s', $this->short, $this->name);
    }
}
