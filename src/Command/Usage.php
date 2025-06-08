<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\Command\Pattern\{
    RequiredArgument,
    OptionalArgument,
    OptionFlag,
    OptionWithValue,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Monoid\Concat,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 */
final class Usage
{
    /**
     * @param non-empty-string $name
     * @param Sequence<RequiredArgument|OptionalArgument> $arguments
     * @param Sequence<OptionFlag|OptionWithValue> $options
     */
    private function __construct(
        private string $name,
        private Sequence $arguments,
        private Sequence $options,
        private bool $pack,
        private ?string $shortDescription,
        private ?string $description,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function of(string $name): self
    {
        return new self(
            $name,
            Sequence::of(),
            Sequence::of(),
            false,
            null,
            null,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public function argument(string $name): self
    {
        $_ = $this
            ->arguments
            ->last()
            ->keep(Instance::of(OptionalArgument::class))
            ->match(
                static fn() => throw new \LogicException('No required argument after an optional one'),
                static fn() => null,
            );

        return new self(
            $this->name,
            ($this->arguments)(RequiredArgument::named($name)),
            $this->options,
            $this->pack,
            $this->shortDescription,
            $this->description,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public function optionalArgument(string $name): self
    {
        return new self(
            $this->name,
            ($this->arguments)(OptionalArgument::named($name)),
            $this->options,
            $this->pack,
            $this->shortDescription,
            $this->description,
        );
    }

    public function packArguments(): self
    {
        return new self(
            $this->name,
            $this->arguments,
            $this->options,
            true,
            $this->shortDescription,
            $this->description,
        );
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $short
     */
    public function option(string $name, ?string $short = null): self
    {
        return new self(
            $this->name,
            $this->arguments,
            ($this->options)(OptionWithValue::named($name, $short)),
            $this->pack,
            $this->shortDescription,
            $this->description,
        );
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $short
     */
    public function flag(string $name, ?string $short = null): self
    {
        return new self(
            $this->name,
            $this->arguments,
            ($this->options)(OptionFlag::named($name, $short)),
            $this->pack,
            $this->shortDescription,
            $this->description,
        );
    }

    public function withShortDescription(string $description): self
    {
        if (Str::of($description)->contains("\n")) {
            throw new \LogicException('Short description cannot contain a new line');
        }

        return new self(
            $this->name,
            $this->arguments,
            $this->options,
            $this->pack,
            $description,
            $this->description,
        );
    }

    public function withDescription(string $description): self
    {
        return new self(
            $this->name,
            $this->arguments,
            $this->options,
            $this->pack,
            $this->shortDescription,
            $description,
        );
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function shortDescription(): string
    {
        return $this->shortDescription ?? '';
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        $string = $this->name;
        $string .= $this
            ->arguments
            ->map(static fn($argument) => ' '.$argument->toString())
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toString();

        if ($this->pack) {
            $string .= ' ...arguments';
        }

        $string .= $this
            ->options
            ->map(static fn($argument) => ' '.$argument->toString())
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toString();
        $string .= ' --help --no-interaction';

        if (\is_string($this->shortDescription)) {
            $string .= "\n\n";
            $string .= $this->shortDescription;
        }

        if (\is_string($this->description)) {
            $string .= "\n\n";
            $string .= $this->description;
        }

        return $string;
    }
}
