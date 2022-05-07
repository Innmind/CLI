<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Inputs
{
    /** list<class-string<Input>> */
    private array $inputs;

    public function __construct()
    {
        $this->inputs = [
            RequiredArgument::class,
            OptionalArgument::class,
            PackArgument::class,
            OptionFlag::class,
            OptionWithValue::class,
        ];
    }

    public function __invoke(Str $pattern): Input
    {
        /** @var Maybe<Input> */
        $parsed = Maybe::nothing();

        /** @var class-string<Input> $input */
        foreach ($this->inputs as $input) {
            $parsed = $parsed->otherwise(static fn() => $input::of($pattern));
        }

        return $parsed->match(
            static fn($input) => $input,
            static fn() => throw new PatternNotRecognized($pattern->toString()),
        );
    }
}
