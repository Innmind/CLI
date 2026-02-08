<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command,
    Command\Pattern\RequiredArgument,
    Command\Pattern\OptionalArgument,
    Command\Pattern\OptionFlag,
    Command\Pattern\OptionWithValue,
    Command\Pattern\Inputs,
};
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Sequence,
    Str,
    Identity,
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
     * @param Identity<bool> $pack
     * @param Identity<?string> $description
     */
    private function __construct(
        private string $name,
        private Sequence $arguments,
        private Sequence $options,
        private Identity $pack,
        private ?string $shortDescription,
        private Identity $description,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    #[\NoDiscard]
    public static function of(string $name): self
    {
        /** @var ?string */
        $description = null;

        return new self(
            $name,
            Sequence::of(),
            Sequence::of(),
            Identity::of(false),
            null,
            Identity::of($description),
        );
    }

    /**
     * @psalm-pure
     *
     * @param class-string<Command> $class
     */
    #[\NoDiscard]
    public static function for(string $class): self
    {
        $refl = new \ReflectionClass($class);
        $attributes = $refl->getAttributes(Name::class);

        foreach ($attributes as $attribute) {
            /** @psalm-suppress ImpureMethodCall */
            $name = $attribute->newInstance();
            $usage = self::of($name->name());

            if (\is_string($name->shortDescription())) {
                $usage = $usage->withShortDescription($name->shortDescription());
            }

            return $usage;
        }

        throw new \LogicException(\sprintf(
            'Missing %s attribute on %s',
            Name::class,
            $class,
        ));
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function parse(string $usage): self
    {
        $declaration = Str::of($usage)->trim();

        if ($declaration->empty()) {
            throw new \LogicException('Empty usage');
        }

        $lines = $declaration->split("\n");
        $name = $lines
            ->first()
            ->map(static fn($line) => $line->split(' '))
            ->flatMap(static fn($parts) => $parts->first())
            ->map(static fn($name) => $name->toString())
            ->keep(Is::string()->nonEmpty()->asPredicate())
            ->match(
                static fn($name) => $name,
                static fn() => throw new \LogicException('Command name not found'),
            );
        $usage = self::of($name);
        $usage = $lines
            ->get(2)
            ->map(static fn($line) => $line->trim()->toString())
            ->match(
                $usage->withShortDescription(...),
                static fn() => $usage,
            );

        $description = $lines
            ->drop(4)
            ->map(static fn($line) => $line->trim()->toString());
        $description = Str::of("\n")->join($description)->toString();

        if ($description !== '') {
            $usage = $usage->withDescription($description);
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return $lines
            ->first()
            ->toSequence()
            ->flatMap(static fn($line) => $line->split(' ')->drop(1))
            ->sink($usage)
            ->attempt(new Inputs)
            ->unwrap();
    }

    /**
     * @param non-empty-string $name
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
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

    #[\NoDiscard]
    public function packArguments(): self
    {
        return new self(
            $this->name,
            $this->arguments,
            $this->options,
            Identity::of(true),
            $this->shortDescription,
            $this->description,
        );
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $short
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
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

    #[\NoDiscard]
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

    #[\NoDiscard]
    public function withDescription(string $description): self
    {
        /** @var ?string */
        $description = $description;

        return new self(
            $this->name,
            $this->arguments,
            $this->options,
            $this->pack,
            $this->shortDescription,
            Identity::of($description),
        );
    }

    /**
     * Use this method to lazy load the rest of the usage.
     *
     * It should be used when the usage is built with self::for().
     *
     * @param callable(): self $load
     */
    #[\NoDiscard]
    public function load(callable $load): self
    {
        $usage = Identity::defer($load);

        return new self(
            $this->name,
            Sequence::lazy(static fn() => yield $usage->unwrap())
                ->flatMap(static fn($usage) => $usage->arguments)
                ->snap(),
            Sequence::lazy(static fn() => yield $usage->unwrap())
                ->flatMap(static fn($usage) => $usage->options)
                ->snap(),
            Identity::defer(static fn() => $usage->unwrap())->flatMap(
                static fn($usage) => $usage->pack,
            ),
            $this->shortDescription,
            Identity::defer(static fn() => $usage->unwrap())->flatMap(
                static fn($usage) => $usage->description,
            ),
        );
    }

    /**
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @internal
     */
    public function is(string $command): bool
    {
        return $this->name === $command;
    }

    /**
     * @internal
     */
    public function matches(string $command): bool
    {
        if ($command === '') {
            return false;
        }

        $command = Str::of($command);
        $name = Str::of($this->name);

        if ($name->equals($command)) {
            return true;
        }

        $commandChunks = $command->trim(':')->split(':');
        $nameChunks = $name->trim(':')->split(':');
        $diff = $nameChunks
            ->zip($commandChunks)
            ->map(static fn($pair) => [
                $pair[0]->take($pair[1]->length()),
                $pair[1],
            ]);

        if ($nameChunks->size() !== $diff->size()) {
            return false;
        }

        return $diff->matches(
            static fn($pair) => $pair[0]->equals($pair[1]),
        );
    }

    #[\NoDiscard]
    public function shortDescription(): string
    {
        return $this->shortDescription ?? '';
    }

    #[\NoDiscard]
    public function pattern(): Pattern
    {
        return new Pattern(
            $this->arguments,
            $this->options,
            $this->pack->unwrap(),
        );
    }

    /**
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function toString(): string
    {
        $string = $this->name;
        $string .= $this
            ->arguments
            ->map(static fn($argument) => ' '.$argument->toString())
            ->map(Str::of(...))
            ->fold(Concat::monoid)
            ->toString();

        if ($this->pack->unwrap()) {
            $string .= ' ...arguments';
        }

        $string .= $this
            ->options
            ->map(static fn($argument) => ' '.$argument->toString())
            ->map(Str::of(...))
            ->fold(Concat::monoid)
            ->toString();
        $string .= ' --help --no-interaction';

        if (\is_string($this->shortDescription)) {
            $string .= "\n\n";
            $string .= $this->shortDescription;
        }

        $description = $this->description->unwrap();

        if (\is_string($description)) {
            $string .= "\n\n";
            $string .= $description;
        }

        return $string;
    }
}
