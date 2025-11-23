<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\Command\Usage;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class UsageTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $usage = Usage::parse(<<<USAGE
            watch container [output] ...proxy

            Watch a container definition file for changes and generate corresponding graph

            The output argument is optional, when ommitted it will print the graphviz dot
            content but if provided it will automatically generate the graph to the given file.

            The proxy pack argument are arguments that will be sent used for the graphviz command.
            USAGE);

        $this->assertSame('watch', $usage->name());
        $this->assertSame(
            'Watch a container definition file for changes and generate corresponding graph',
            $usage->shortDescription(),
        );

        $expected = <<<USAGE
        watch container [output] ...arguments --help --no-interaction

        Watch a container definition file for changes and generate corresponding graph

        The output argument is optional, when ommitted it will print the graphviz dot
        content but if provided it will automatically generate the graph to the given file.

        The proxy pack argument are arguments that will be sent used for the graphviz command.
        USAGE;

        $this->assertSame($expected, $usage->toString());
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
                $usage = Usage::parse($a);

                $this->assertTrue($usage->matches($a));
                $this->assertFalse($usage->matches($b));
            });
    }

    public function testMatchesItsOwnNameRegression()
    {
        $a = ':';
        $b = 'ᰀ';

        $usage = Usage::parse($a);

        $this->assertTrue($usage->matches($a));
        $this->assertFalse($usage->matches($b));
    }

    public function testMatchesStartOfItsOwnName(): BlackBox\Proof
    {
        return $this
            ->forAll(
                $this->names(),
                Set::integers()->between(1, 10),
            )
            ->prove(function($name, $shrink) {
                $usage = Usage::parse($name);
                $shrunk = \mb_substr($name, 0, $shrink);

                $this->assertTrue($usage->matches($shrunk));
            });
    }

    public function testMatchesStartOfSectionsOfItsOwnName(): BlackBox\Proof
    {
        return $this
            ->forAll($this->chunks())
            ->prove(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = \implode(':', \array_column($chunks, 'shrunk'));

                $usage = Usage::parse($name);

                $this->assertTrue($usage->matches($shrunk));
            });
    }

    public function testMatchesStartOfSectionsOfItsOwnNameRegression()
    {
        $name = '둰ᰓ⁽𑐥𑓏:';
        $shrunk = '둰ᰓ⁽';

        $usage = Usage::parse($name);

        $this->assertTrue($usage->matches($shrunk));
    }

    public function testDoesnMatchLessSectionProvidedThanExpected(): BlackBox\Proof
    {
        return $this
            ->forAll($this->chunks(2))
            ->prove(function($chunks) {
                $name = \implode(':', \array_column($chunks, 'name'));
                $shrunk = $chunks[0]['shrunk'];

                $usage = Usage::parse($name);

                $this->assertFalse($usage->matches($shrunk));
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
            ->filter(static fn($name, $start, $shrink) => !\str_starts_with(
                $name,
                \mb_substr($name, $start, $shrink),
            ))
            ->prove(function($name, $start, $shrink) {
                $usage = Usage::parse($name);
                $shrunk = \mb_substr($name, $start, $shrink);

                $this->assertFalse($usage->matches($shrunk));
            });
    }

    public function testThrowWhenEmptyDeclaration()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Empty usage');

        Usage::parse('  ');
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
