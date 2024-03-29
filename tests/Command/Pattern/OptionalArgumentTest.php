<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionalArgument,
    Command\Pattern\Input,
    Command\Pattern\Argument,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
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
        $this->assertInstanceOf(
            Argument::class,
            OptionalArgument::of(Str::of('[foo]'))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9]+$~', $s),
            ))
            ->then(function(string $string): void {
                $this->assertNull(OptionalArgument::of(Str::of('['.$string.']'))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('[foo]', '[bar]', '[baz]'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionalArgument::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testParse()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Strings::atLeast(1),
            )->between(1, 10))
            ->then(function($strings) {
                $input = OptionalArgument::of(Str::of('[foo]'))->match(
                    static fn($input) => $input,
                    static fn() => null,
                );

                [$arguments, $parsedArguments, $pack, $options] = $input->parse(
                    Sequence::of(...$strings),
                    Map::of(),
                    Sequence::of(),
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
                $this->assertTrue($pack->empty());
                $this->assertTrue($options->empty());
            });
    }

    public function testParseWhenNoMoreArguments()
    {
        $input = OptionalArgument::of(Str::of('[foo]'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of(),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertCount(0, $parsedArguments);
        $this->assertNull($parsedArguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertTrue($arguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertTrue($options->empty());
    }
}
