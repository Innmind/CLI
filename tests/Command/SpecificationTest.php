<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Specification,
    Command\Pattern,
    Command,
    Console,
    Exception\EmptyDeclaration,
};
use Innmind\Immutable\Attempt;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class SpecificationTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $command = new class implements Command {
            public function __invoke(Console $console): Attempt
            {
            }

            public function usage(): string
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
            $spec->shortDescription(),
        );

        $expected = <<<DESCRIPTION
The output argument is optional, when ommitted it will print the graphviz dot
content but if provided it will automatically generate the graph to the given file.

The proxy pack argument are arguments that will be sent used for the graphviz command.
DESCRIPTION;

        $this->assertSame($expected, $spec->description());
        $this->assertSame(
            'watch container [output] ...proxy --help --no-interaction',
            $spec->toString(),
        );
        $this->assertInstanceOf(Pattern::class, $spec->pattern());
        $this->assertSame(
            'container [output] ...proxy --help --no-interaction',
            $spec->pattern()->toString(),
        );
    }

    public function testMatchesItsOwnName(): BlackBox\Proof
    {
        return $this
            ->forAll(
                $this->names(),
                $this->names(),
            )
            ->filter(static fn($a, $b) => $a !== $b)
            ->prove(function($a, $b) {
                $command = new class($a) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Console $console): Attempt
                    {
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertTrue($spec->matches($a));
                $this->assertFalse($spec->matches($b));
            });
    }

    public function testMatchesItsOwnNameRegression()
    {
        $a = ':';
        $b = 'ᰀ';
        $command = new class($a) implements Command {
            private $usage;

            public function __construct(string $usage)
            {
                $this->usage = $usage;
            }

            public function __invoke(Console $console): Attempt
            {
            }

            public function usage(): string
            {
                return $this->usage;
            }
        };

        $spec = new Specification($command);

        $this->assertTrue($spec->matches($a));
        $this->assertFalse($spec->matches($b));
    }

    public function testMatchesStartOfItsOwnName(): BlackBox\Proof
    {
        return $this
            ->forAll(
                $this->names(),
                Set::integers()->between(1, 10),
            )
            ->prove(function($name, $shrink) {
                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Console $console): Attempt
                    {
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);
                $shrunk = \mb_substr($name, 0, $shrink);

                $this->assertTrue($spec->matches($shrunk));
            });
    }

    public function testMatchesStartOfSectionsOfItsOwnName(): BlackBox\Proof
    {
        return $this
            ->forAll($this->chunks())
            ->prove(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = \implode(':', \array_column($chunks, 'shrunk'));

                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Console $console): Attempt
                    {
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertTrue($spec->matches($shrunk));
            });
    }

    public function testMatchesStartOfSectionsOfItsOwnNameRegression()
    {
        $name = '둰ᰓ⁽𑐥𑓏:';
        $shrunk = '둰ᰓ⁽';

        $command = new class($name) implements Command {
            private $usage;

            public function __construct(string $usage)
            {
                $this->usage = $usage;
            }

            public function __invoke(Console $console): Attempt
            {
            }

            public function usage(): string
            {
                return $this->usage;
            }
        };

        $spec = new Specification($command);

        $this->assertTrue($spec->matches($shrunk));
    }

    public function testDoesnMatchLessSectionProvidedThanExpected(): BlackBox\Proof
    {
        return $this
            ->forAll($this->chunks(2))
            ->prove(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = $chunks[0]['shrunk'];

                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Console $console): Attempt
                    {
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };

                $spec = new Specification($command);

                $this->assertFalse($spec->matches($shrunk));
            });
    }

    public function testDoesntMatchWhenOwnNameDoesntExplicitlyStartWithSubset(): BlackBox\Proof
    {
        return $this
            ->forAll(
                $this->names(),
                Set::integers()->between(1, 10),
                Set::integers()->between(1, 10),
            )
            ->prove(function($name, $start, $shrink) {
                $command = new class($name) implements Command {
                    private $usage;

                    public function __construct(string $usage)
                    {
                        $this->usage = $usage;
                    }

                    public function __invoke(Console $console): Attempt
                    {
                    }

                    public function usage(): string
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
            public function __invoke(Console $console): Attempt
            {
            }

            public function usage(): string
            {
                return '  ';
            }
        };

        $this->expectException(EmptyDeclaration::class);

        (new Specification($command))->toString();
    }

    private function names(): Set
    {
        return Set::strings()
            ->madeOf(
                Set::strings()
                    ->unicode()
                    ->char()
                    ->filter(
                        static fn($char) => !\in_array(
                            $char,
                            [
                                ':',
                                ' ',
                                "\n",
                                "\r",
                                \chr(11),
                                \chr(0),
                                "\t",
                            ],
                            true,
                        ),
                    ),
            )
            ->between(1, 10);
    }

    private function chunks(int $min = 1): Set
    {
        return Set::sequence(
            Set::compose(
                static fn($name, $shrink) => [
                    'name' => $name,
                    'shrunk' => \mb_substr($name, 0, $shrink),
                ],
                $this->names(),
                Set::integers()->between(1, 9),
            ),
        )
            ->between($min, 5)
            ->toSet();
    }
}
