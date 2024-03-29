<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionWithValue,
    Command\Pattern\Input,
    Command\Pattern\Option,
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

class OptionWithValueTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Input::class,
            OptionWithValue::of(Str::of('--foo='))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Option::class,
            OptionWithValue::of(Str::of('--foo='))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9\-]+$~', $s),
            ))
            ->then(function(string $string): void {
                $string = '--'.$string.'=';

                $this->assertNull(OptionWithValue::of(Str::of($string))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('--foo=', '-b|--bar=', '--baz='))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionWithValue::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testParseShortOption()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of('watev', '-f=baz', 'bar'),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertSame(['watev', 'bar'], $arguments->toList());
        $this->assertTrue($parsedArguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertCount(1, $options);
        $this->assertSame('baz', $options->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testParseShortOptionWithValueInNextArgument()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of('watev', '-f', 'baz', 'bar'),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertSame(['watev', 'bar'], $arguments->toList());
        $this->assertTrue($parsedArguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertCount(1, $options);
        $this->assertSame('baz', $options->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testParseShortOptionWithValueInNextArgumentButNoNextValue()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of('watev', '-f'),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertSame(['watev'], $arguments->toList());
        $this->assertTrue($parsedArguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertCount(1, $options);
        $this->assertSame('', $options->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testParseLongOption()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of('watev', '--foo=baz', 'bar'),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertSame(['watev', 'bar'], $arguments->toList());
        $this->assertTrue($parsedArguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertCount(1, $options);
        $this->assertSame('baz', $options->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testParseWhenNoOption()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $parsedArguments, $pack, $options] = $input->parse(
            Sequence::of('watev', '--unknown', 'foo'),
            Map::of(),
            Sequence::of(),
            Map::of(),
        );

        $this->assertSame(['watev', '--unknown', 'foo'], $arguments->toList());
        $this->assertTrue($parsedArguments->empty());
        $this->assertTrue($pack->empty());
        $this->assertTrue($options->empty());
    }
}
