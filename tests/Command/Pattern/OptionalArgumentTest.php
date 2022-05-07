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

    public function testExtract()
    {
        $input = OptionalArgument::of(Str::of('[foo]'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        $arguments = $input->extract(
            Map::of(),
            0,
            $args = Sequence::of('watev', 'foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertSame('watev', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testDoNothingWhenArgumentNotFound()
    {
        $input = OptionalArgument::of(Str::of('[foo]'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        $arguments = $input->extract(
            $expected = Map::of(),
            42,
            Sequence::of('watev', 'foo', 'bar', 'baz'),
        );

        $this->assertSame($expected, $arguments);
    }
}
