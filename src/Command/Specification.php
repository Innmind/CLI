<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command;

use Innmind\CLI\{
    Command,
    Exception\EmptyDeclaration,
    Exception\LogicException,
};
use Innmind\Immutable\Str;
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class Specification
{
    private string $name;
    private string $shortDescription = '';
    private string $description = '';
    private Pattern $pattern;

    public function __construct(Command $command)
    {
        $declaration = Str::of($command->toString())->trim();

        if ($declaration->empty()) {
            throw new EmptyDeclaration;
        }

        $parts = $declaration->split("\n");

        if ($parts->empty()) {
            throw new LogicException('Missing command pattern');
        }

        [$this->name, $this->pattern] = $this->buildPattern($parts->first());

        if ($parts->size() >= 3) {
            //get(2) as there must be a blank line before
            $this->shortDescription = $parts->get(2)->trim()->toString();
        }

        if ($parts->size() >= 5) {
            //drop(4) as there must be a blank line before
            $lines = $parts
                ->drop(4)
                ->map(static function(Str $line): Str {
                    return $line->trim();
                })
                ->mapTo(
                    'string',
                    static fn(Str $line): string => $line->toString(),
                );
            $this->description = join("\n", $lines)->toString();
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function shortDescription(): string
    {
        return $this->shortDescription;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function pattern(): Pattern
    {
        return $this->pattern;
    }

    public function toString(): string
    {
        return $this->name.' '.$this->pattern->toString();
    }

    private function buildPattern(Str $pattern): array
    {
        $elements = $pattern->trim()->split(' ');
        $name = $elements->first()->toString();

        return [$name, new Pattern(...unwrap($elements->drop(1)))];
    }
}
