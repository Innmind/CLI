<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\OptionalArgument,
    Command\Pattern\Input,
    Command\Pattern\Argument,
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

class OptionalArgumentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, OptionalArgument::of(Str::of('[foo]')));
        $this->assertInstanceOf(Argument::class, OptionalArgument::of(Str::of('[foo]')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !preg_match('~^[a-zA-Z0-9]+$~', $s),
            ))
            ->then(function(string $string): void {
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage('['.$string.']');

                OptionalArgument::of(Str::of('['.$string.']'));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('[foo]', '[bar]', '[baz]'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    OptionalArgument::of(Str::of($string))->toString(),
                );
            });
    }

    public function testExtract()
    {
        $input = OptionalArgument::of(Str::of('[foo]'));

        $arguments = $input->extract(
            Map::of('string', 'mixed'),
            0,
            $args = Sequence::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertSame('watev', $arguments->get('foo'));
    }

    public function testDoNothingWhenArgumentNotFound()
    {
        $input = OptionalArgument::of(Str::of('[foo]'));

        $arguments = $input->extract(
            $expected = Map::of('string', 'mixed'),
            42,
            Sequence::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertSame($expected, $arguments);
    }
}
