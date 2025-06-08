<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Usage,
    Exception\PatternNotRecognized,
};
use Innmind\Immutable\{
    Attempt,
    Str,
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Inputs
{
    /** @var Sequence<class-string<Input>> */
    private Sequence $inputs;

    public function __construct()
    {
        $this->inputs = Sequence::of(
            RequiredArgument::class,
            OptionalArgument::class,
            PackArgument::class,
            OptionFlag::class,
            OptionWithValue::class,
        );
    }

    public function __invoke(Str $pattern): Input
    {
        /** @var ?Input */
        $parsed = null;

        $input = $this
            ->inputs
            ->sink($parsed)
            ->until(static fn($parsed, $input, $continuation) => match ($parsed) {
                null => $input::of($pattern)->match(
                    static fn($input) => $continuation->stop($input),
                    static fn() => $continuation->continue($parsed),
                ),
                default => $continuation->stop($parsed),
            });

        return Maybe::of($input)
            ->keep(Instance::of(Input::class))
            ->attempt(static fn() => new PatternNotRecognized($pattern->toString()))
            ->unwrap();
    }

    /**
     * @return Attempt<Usage>
     */
    public function walk(Usage $usage, Str $pattern): Attempt
    {
        $parsed = $this
            ->inputs
            ->sink($usage)
            ->until(static fn($usage, $input, $continuation) => $input::walk($usage, $pattern)->match(
                static fn($usage) => $continuation->stop($usage),
                static fn() => $continuation->continue($usage),
            ));

        return match ($parsed) {
            $usage => Attempt::error(new PatternNotRecognized($pattern->toString())),
            default => Attempt::result($parsed),
        };
    }
}
