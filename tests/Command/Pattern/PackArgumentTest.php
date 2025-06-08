<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\PackArgument,
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
    }

    public function testReturnNothingWhenInvalidPattern(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9]+$~', $s),
            ))
            ->prove(function(string $string): void {
                $this->assertNull(PackArgument::of(Str::of('...'.$string))->match(
                    static fn($input) => $input,
                    static fn() => null,
                ));
            });
    }

    public function testStringCast(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('...foo', '...bar', '...baz'))
            ->prove(function(string $string): void {
                $this->assertSame(
                    $string,
                    PackArgument::of(Str::of($string))->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    ),
                );
            });
    }

    public function testParse(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::sequence(
                Set::strings()->atLeast(1),
            )->between(0, 10))
            ->prove(function($strings) {
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
