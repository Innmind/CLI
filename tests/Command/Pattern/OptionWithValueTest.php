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
    Stream,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class OptionWithValueTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, OptionWithValue::fromString(Str::of('--foo=')));
        $this->assertInstanceOf(Option::class, OptionWithValue::fromString(Str::of('--foo=')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return !preg_match('~^[a-zA-Z0-9]+$~', $string);
            })
            ->then(function(string $string): void {
                $string = '--'.$string.'=';
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage($string);

                OptionWithValue::fromString(Str::of($string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Generator\elements('--foo=', '-b|--bar=', '--baz='))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    (string) OptionWithValue::fromString(Str::of($string))
                );
            });
    }

    public function testExtract()
    {
        $input = OptionWithValue::fromString(Str::of('--foo='));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            0,
            Stream::of('string', 'watev', '--foo=42', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertSame('42', $arguments->get('foo'));
    }

    public function testExtractShortOptionWithValueRightAfterIt()
    {
        $input = OptionWithValue::fromString(Str::of('-f|--foo='));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            0,
            Stream::of('string', 'watev', '-f=42', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertSame('42', $arguments->get('foo'));
    }

    public function testExtractShortOptionWithValueAsNextArgument()
    {
        $input = OptionWithValue::fromString(Str::of('-f|--foo='));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            0,
            Stream::of('string', 'watev', '-f', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertSame('bar', $arguments->get('foo'));
    }

    public function testDoesNothingWhenNoOption()
    {
        $input = OptionWithValue::fromString(Str::of('--foo='));

        $arguments = $input->extract(
            $expected = new Map('string', 'mixed'),
            42,
            Stream::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertSame($expected, $arguments);
    }

    public function testDoesNothingWhenNoShortOption()
    {
        $input = OptionWithValue::fromString(Str::of('-f|--foo='));

        $arguments = $input->extract(
            $expected = new Map('string', 'mixed'),
            42,
            Stream::of('string', 'watev', 'f', 'bar', 'baz')
        );

        $this->assertSame($expected, $arguments);
    }
}
