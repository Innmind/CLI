<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionWithValue,
    Command\Pattern\Input,
    Command\Pattern\Option,
    Exception\MissingArgument,
    Exception\PatternNotRecognized,
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
        $this->assertInstanceOf(Input::class, OptionWithValue::of(Str::of('--foo=')));
        $this->assertInstanceOf(Option::class, OptionWithValue::of(Str::of('--foo=')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9\-]+$~', $s),
            ))
            ->then(function(string $string): void {
                $string = '--'.$string.'=';
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage($string);

                OptionWithValue::of(Str::of($string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('--foo=', '-b|--bar=', '--baz='))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionWithValue::of(Str::of($string))->toString(),
                );
            });
    }

    public function testExtract()
    {
        $input = OptionWithValue::of(Str::of('--foo='));

        $arguments = $input->extract(
            Map::of(),
            0,
            Sequence::of('watev', '--foo=42', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertSame('42', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testExtractShortOptionWithValueRightAfterIt()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->extract(
            Map::of(),
            0,
            Sequence::of('watev', '-f=42', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertSame('42', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testExtractShortOptionWithValueAsNextArgument()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->extract(
            Map::of(),
            0,
            Sequence::of('watev', '-f', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertSame('bar', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testDoesNothingWhenNoOption()
    {
        $input = OptionWithValue::of(Str::of('--foo='));

        $arguments = $input->extract(
            $expected = Map::of(),
            42,
            Sequence::of('watev', 'foo', 'bar', 'baz'),
        );

        $this->assertSame($expected, $arguments);
    }

    public function testDoesNothingWhenNoShortOption()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->extract(
            $expected = Map::of(),
            42,
            Sequence::of('watev', 'f', 'bar', 'baz'),
        );

        $this->assertSame($expected, $arguments);
    }

    public function testCleanWhenNoOption()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->clean(
            $expected = Sequence::of('watev', 'f', 'bar', 'baz'),
        );

        $this->assertSame($expected, $arguments);
    }

    public function testCleanWhenOptionWithValueAttached()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->clean(
            Sequence::of('watev', '--foo=foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame(['watev', 'bar', 'baz'], $arguments->toList());
    }

    public function testCleanWhenShortOptionWithValueAttached()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->clean(
            Sequence::of('watev', '-f=foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame(['watev', 'bar', 'baz'], $arguments->toList());
    }

    public function testCleanWhenShortOptionWithValueAsNextArgument()
    {
        $input = OptionWithValue::of(Str::of('-f|--foo='));

        $arguments = $input->clean(
            Sequence::of('watev', '-f', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame(['watev', 'baz'], $arguments->toList());
    }
}
