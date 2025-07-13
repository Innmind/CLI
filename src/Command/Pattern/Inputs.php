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
    Sequence,
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

    /**
     * @return Attempt<Usage>
     */
    public function __invoke(Usage $usage, Str $pattern): Attempt
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
