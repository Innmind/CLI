<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\PackArgument,
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

class PackArgumentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Input::class,
            PackArgument::of(Str::of('...foo'))->match(
                static fn($input) => $input,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Argument::class,
            PackArgument::of(Str::of('...foo'))->match(
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
                $this->assertNull(PackArgument::of(Str::of('...'.$string))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('...foo', '...bar', '...baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    PackArgument::of(Str::of($string))->match(
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
            )->between(0, 10))
            ->then(function($strings) {
                $input = PackArgument::of(Str::of('...foo'))->match(
                    static fn($input) => $input,
                    static fn() => null,
                );

                [$arguments, $parsedArguments, $pack, $options] = $input->parse(
                    Sequence::of(...$strings),
                    Map::of(),
                    Sequence::of(),
                    Map::of(),
                );

                $this->assertTrue(
                    $pack->equals(
                        Sequence::of(...$strings),
                    ),
                );
                $this->assertTrue($arguments->empty());
                $this->assertTrue($parsedArguments->empty());
                $this->assertTrue($options->empty());
            });
    }
}
