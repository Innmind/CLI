<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionFlag,
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

class OptionFlagTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Input::class,
            OptionFlag::of(Str::of('--foo'))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Option::class,
            OptionFlag::of(Str::of('--foo'))->match(
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
                $string = '--'.$string;

                $this->assertNull(OptionFlag::of(Str::of($string))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('--foo', '-b|--bar', '--baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionFlag::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testExtract()
    {
        $input = OptionFlag::of(Str::of('--foo'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        $arguments = $input->extract(
            Map::of(),
            0,
            Sequence::of('watev', '--foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertSame('', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testDoesNothingWhenNoFlag()
    {
        $input = OptionFlag::of(Str::of('--foo'))->match(
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

    public function testClean()
    {
        $input = OptionFlag::of(Str::of('-f|--foo'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        $arguments = $input->clean(
            Sequence::of('watev', '--foo', 'bar', 'baz', '-f'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame(['watev', 'bar', 'baz'], $arguments->toList());
    }
}
