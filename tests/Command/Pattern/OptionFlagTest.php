<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionFlag,
    Command\Pattern\Input,
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

class OptionFlagTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, OptionFlag::fromString(Str::of('--foo')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return !preg_match('~^[a-zA-Z0-9]+$~', $string);
            })
            ->then(function(string $string): void {
                $string = '--'.$string;
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage($string);

                OptionFlag::fromString(Str::of($string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Generator\elements('--foo', '-b|--bar', '--baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    (string) OptionFlag::fromString(Str::of($string))
                );
            });
    }

    public function testExtract()
    {
        $input = OptionFlag::fromString(Str::of('--foo'));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            0,
            Stream::of('string', 'watev', '--foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertTrue($arguments->get('foo'));
    }

    public function testDoesNothingWhenNoFlag()
    {
        $input = OptionFlag::fromString(Str::of('--foo'));

        $arguments = $input->extract(
            $expected = new Map('string', 'mixed'),
            42,
            Stream::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertSame($expected, $arguments);
    }
}
