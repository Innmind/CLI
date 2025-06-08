<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\PackArgument,
    Command\Pattern\Input,
    Command\Pattern,
};
use Innmind\Immutable\{
    Str,
    Sequence,
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
                    '...arguments',
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
                $pattern = new Pattern(
                    Sequence::of(),
                    Sequence::of(),
                    true,
                );

                [$arguments] = $pattern(
                    Sequence::of(...$strings),
                );
                $pack = $arguments->pack();

                $this->assertTrue(
                    $pack->equals(
                        Sequence::of(...$strings),
                    ),
                );
            });
    }
}
