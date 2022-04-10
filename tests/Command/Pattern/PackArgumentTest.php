<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\PackArgument,
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

class PackArgumentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, PackArgument::of(Str::of('...foo')));
        $this->assertInstanceOf(Argument::class, PackArgument::of(Str::of('...foo')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Set\Strings::any()->filter(
                static fn(string $s) => !\preg_match('~^[a-zA-Z0-9]+$~', $s),
            ))
            ->then(function(string $string): void {
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage('...'.$string);

                PackArgument::of(Str::of('...'.$string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Set\Elements::of('...foo', '...bar', '...baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    PackArgument::of(Str::of($string))->toString(),
                );
            });
    }

    public function testExtract()
    {
        $input = PackArgument::of(Str::of('...foo'));

        $arguments = $input->extract(
            Map::of(),
            1,
            Sequence::of('watev', 'foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Sequence::class, $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(['foo', 'bar', 'baz'], $arguments->get('foo')->match(
            static fn($value) => $value->toList(),
            static fn() => null,
        ));
    }

    public function testExtractEmptyStreamWhenNotFound()
    {
        $input = PackArgument::of(Str::of('...foo'));

        $arguments = $input->extract(
            Map::of(),
            42,
            Sequence::of('watev', 'foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Sequence::class, $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertTrue($arguments->get('foo')->match(
            static fn($value) => $value->empty(),
            static fn() => null,
        ));
    }
}
