<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionalArgument,
    Command\Pattern\Input,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class OptionalArgumentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Input::class,
            OptionalArgument::of(Str::of('[foo]'))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidPattern(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9]+$~', $s),
            ))
            ->prove(function(string $string): void {
                $this->assertNull(OptionalArgument::of(Str::of('['.$string.']'))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('[foo]', '[bar]', '[baz]'))
            ->prove(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionalArgument::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testParse(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::sequence(
                Set::strings()->atLeast(1),
            )->between(1, 10))
            ->prove(function($strings) {
                $input = OptionalArgument::of(Str::of('[foo]'))->match(
                    static fn($input) => $input,
                    static fn() => null,
                );

                [$arguments, $parsedArguments] = $input->parse(
                    Sequence::of(...$strings),
                    Map::of(),
                );

                $this->assertCount(1, $parsedArguments);
                $this->assertSame($strings[0], $parsedArguments->get('foo')->match(
                    static fn($value) => $value,
                    static fn() => null,
                ));
                $this->assertTrue(
                    $arguments->equals(
                        Sequence::of(...$strings)->drop(1),
                    ),
                );
            });
    }

    public function testParseWhenNoMoreArguments()
    {
        $input = OptionalArgument::of(Str::of('[foo]'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments] = $input->parse(
            Sequence::of(),
            Map::of(),
        );

        $this->assertCount(0, $parsedArguments);
        $this->assertNull($parsedArguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertTrue($arguments->empty());
    }
}
