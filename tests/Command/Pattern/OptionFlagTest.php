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
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class OptionFlagTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, OptionFlag::of(Str::of('--foo')));
        $this->assertInstanceOf(Option::class, OptionFlag::of(Str::of('--foo')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9\-]+$~', $s),
            ))
            ->then(function(string $string): void {
                $string = '--'.$string;
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage($string);

                OptionFlag::of(Str::of($string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('--foo', '-b|--bar', '--baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionFlag::of(Str::of($string))->toString(),
                );
            });
    }

    public function testExtract()
    {
        $input = OptionFlag::of(Str::of('--foo'));

        $arguments = $input->extract(
            Map::of('string', 'mixed'),
            0,
            Sequence::of('string', 'watev', '--foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertSame('', $arguments->get('foo'));
    }

    public function testDoesNothingWhenNoFlag()
    {
        $input = OptionFlag::of(Str::of('--foo'));

        $arguments = $input->extract(
            $expected = Map::of('string', 'mixed'),
            42,
            Sequence::of('string', 'watev', 'foo', 'bar', 'baz'),
        );

        $this->assertSame($expected, $arguments);
    }

    public function testClean()
    {
        $input = OptionFlag::of(Str::of('-f|--foo'));

        $arguments = $input->clean(
            Sequence::of('string', 'watev', '--foo', 'bar', 'baz', '-f'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame('string', (string) $arguments->type());
        $this->assertSame(['watev', 'bar', 'baz'], unwrap($arguments));
    }
}
