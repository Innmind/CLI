<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Specification,
    Command\Arguments,
    Command\Options,
    Command\Pattern,
    Command,
    Environment,
    Exception\EmptyDeclaration,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class SpecificationTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function toString(): string
            {
                return <<<USAGE
    watch container [output] ...proxy

    Watch a container definition file for changes and generate corresponding graph

    The output argument is optional, when ommitted it will print the graphviz dot
    content but if provided it will automatically generate the graph to the given file.

    The proxy pack argument are arguments that will be sent used for the graphviz command.
USAGE;
            }
        };

        $spec = new Specification($command);

        $this->assertSame('watch', $spec->name());
        $this->assertSame(
            'Watch a container definition file for changes and generate corresponding graph',
            $spec->shortDescription()
        );

        $expected = <<<DESCRIPTION
The output argument is optional, when ommitted it will print the graphviz dot
content but if provided it will automatically generate the graph to the given file.

The proxy pack argument are arguments that will be sent used for the graphviz command.
DESCRIPTION;

        $this->assertSame($expected, $spec->description());
        $this->assertSame(
            'watch container [output] ...proxy',
            $spec->toString(),
        );
        $this->assertInstanceOf(Pattern::class, $spec->pattern());
        $this->assertSame('container [output] ...proxy', $spec->pattern()->toString());
    }

    public function testMatchesItsOwnName()
    {
        $this
            ->forAll(
                $this->name(),
                $this->name(),
            )
            ->filter(fn($a, $b) => $a !== $b)
            ->then(function($a, $b) {
                $command = new class($a) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                    {
                    }

                    public function toString(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertTrue($spec->matches($a));
                $this->assertFalse($spec->matches($b));
            });
    }

    public function testMatchesStartOfItsOwnName()
    {
        $this
            ->forAll(
                $this->name(),
                Set\Integers::between(1, 10),
            )
            ->then(function($name, $shrink) {
                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                    {
                    }

                    public function toString(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);
                $shrunk = \mb_substr($name, 0, $shrink);

                $this->assertTrue($spec->matches($shrunk));
            });
    }

    public function testMatchesStartOfSectionsOfItsOwnName()
    {
        $this
            ->forAll($this->chunks())
            ->then(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = \implode(':', \array_column($chunks, 'shrunk'));

                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                    {
                    }

                    public function toString(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertTrue($spec->matches($shrunk));
            });
    }

    public function testDoesnMatchLessSectionProvidedThanExpected()
    {
        $this
            ->forAll($this->chunks(2))
            ->then(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = $chunks[0]['shrunk'];

                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                    {
                    }

                    public function toString(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertFalse($spec->matches($shrunk));
            });
    }

    public function testDoesntMatchWhenOwnNameDoesntExplicitlyStartWithSubset()
    {
        $this
            ->forAll(
                $this->name(),
                Set\Integers::between(1, 10),
                Set\Integers::between(1, 10),
            )
            ->then(function($name, $start, $shrink) {
                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                    {
                    }

                    public function toString(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);
                $shrunk = \mb_substr($name, $start, $shrink);

                $this->assertFalse($spec->matches($shrunk));
            });
    }

    public function testThrowWhenEmptyDeclaration()
    {
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function toString(): string
            {
                return '  ';
            }
        };

        $this->expectException(EmptyDeclaration::class);

        (new Specification($command))->toString();
    }

    private function name(): Set
    {
        return Set\Unicode::lengthBetween(1, 10)
            ->filter(static fn($s) => \strpos($s, ' ') === false)
            ->filter(static fn($s) => \strpos($s, "\n") === false)
            ->filter(static fn($s) => \strpos($s, "\r") === false)
            ->filter(static fn($s) => \strpos($s, \chr(11)) === false)
            ->filter(static fn($s) => \strpos($s, \chr(0)) === false)
            ->filter(static fn($s) => \strpos($s, "\t") === false);
    }

    private function chunks(int $min = 1): Set
    {
        return Set\Sequence::of(
            Set\Composite::immutable(
                static fn($name, $shrink) => [
                    'name' => $name,
                    'shrunk' => \mb_substr($name, 0, $shrink),
                ],
                $this->name(),
                Set\Integers::between(1, 9),
            ),
            Set\Integers::between($min, 5),
        );
    }
}
