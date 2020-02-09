<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionFlag,
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
use function Innmind\Immutable\unwrap;
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
        $this->assertInstanceOf(Option::class, OptionFlag::fromString(Str::of('--foo')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return !preg_match('~^[a-zA-Z0-9\-]+$~', $string);
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
            Map::of('string', 'mixed'),
            0,
            Sequence::of('string', 'watev', '--foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertTrue($arguments->get('foo'));
    }

    public function testDoesNothingWhenNoFlag()
    {
        $input = OptionFlag::fromString(Str::of('--foo'));

        $arguments = $input->extract(
            $expected = Map::of('string', 'mixed'),
            42,
            Sequence::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertSame($expected, $arguments);
    }

    public function testClean()
    {
        $input = OptionFlag::fromString(Str::of('-f|--foo'));

        $arguments = $input->clean(
            Sequence::of('string', 'watev', '--foo', 'bar', 'baz', '-f')
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame('string', (string) $arguments->type());
        $this->assertSame(['watev', 'bar', 'baz'], unwrap($arguments));
    }
}
