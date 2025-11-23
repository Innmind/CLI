<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionFlag,
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
    }

    public function testReturnNothingWhenInvalidPattern(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9\-]+$~', $s),
            ))
            ->prove(function(string $string): void {
                $string = '--'.$string;

                $this->assertNull(OptionFlag::of(Str::of($string))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('--foo', '-b|--bar', '--baz'))
            ->prove(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionFlag::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testParse()
    {
        $input = OptionFlag::of(Str::of('-f|--foo'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $options] = $input->parse(
            Sequence::of('watev', '--foo', 'bar', '--unknown', 'baz', '-f'),
            Map::of(),
        );

        $this->assertSame(['watev', 'bar', '--unknown', 'baz'], $arguments->toList());
        $this->assertSame(1, $options->size());
        $this->assertSame('', $options->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testParseWhenNoOption()
    {
        $input = OptionFlag::of(Str::of('-f|--foo'))->match(
            static fn($input) => $input,
            static fn() => null,
        );

        [$arguments, $options] = $input->parse(
            Sequence::of('watev', 'bar', '--unknown', 'baz'),
            Map::of(),
        );

        $this->assertSame(['watev', 'bar', '--unknown', 'baz'], $arguments->toList());
        $this->assertTrue($options->empty());
    }
}
