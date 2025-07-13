<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Pattern\RequiredArgument,
    Command\Pattern\OptionalArgument,
    Command\Pattern\OptionFlag,
    Command\Pattern\OptionWithValue,
};
use Innmind\Immutable\{
    Sequence,
    Map,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Pattern
{
    /**
     * @param Sequence<RequiredArgument|OptionalArgument> $arguments
     * @param Sequence<OptionFlag|OptionWithValue> $options
     */
    public function __construct(
        private Sequence $arguments,
        private Sequence $options,
        private bool $pack,
    ) {
    }

    /**
     * @param Sequence<string> $arguments
     *
     * @return array{Arguments, Options}
     */
    public function __invoke(Sequence $arguments): array
    {
        /** @var Map<string, string> */
        $parsedArguments = Map::of();
        $pack = Sequence::strings();
        /** @var Map<string, string> */
        $options = Map::of();

        /** @psalm-suppress MixedArgument */
        [$arguments, $options] = $this->options->reduce(
            [$arguments, $options],
            static fn($carry, $input) => $input->parse(...$carry),
        );
        /** @psalm-suppress MixedArgument */
        [$arguments, $parsedArguments] = $this->arguments->reduce(
            [$arguments, $parsedArguments],
            static fn($carry, $input) => $input->parse(...$carry),
        );

        if ($this->pack) {
            $pack = $arguments;
        }

        return [new Arguments($parsedArguments, $pack), new Options($options)];
    }
}
