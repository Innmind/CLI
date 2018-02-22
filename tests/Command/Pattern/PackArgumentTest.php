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
    StreamInterface,
    Stream,
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class PackArgumentTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(Input::class, PackArgument::fromString(Str::of('...foo')));
        $this->assertInstanceOf(Argument::class, PackArgument::fromString(Str::of('...foo')));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return !preg_match('~^[a-zA-Z0-9]+$~', $string);
            })
            ->then(function(string $string): void {
                $this->expectException(PatternNotRecognized::class);
                $this->expectExceptionMessage('...'.$string);

                PackArgument::fromString(Str::of('...'.$string));
            });
    }

    public function testStringCast()
    {
        $this
            ->forAll(Generator\elements('...foo', '...bar', '...baz'))
            ->then(function(string $string): void {
                $this->assertSame(
                    $string,
                    (string) PackArgument::fromString(Str::of($string))
                );
            });
    }

    public function testExtract()
    {
        $input = PackArgument::fromString(Str::of('...foo'));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            1,
            Stream::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(StreamInterface::class, $arguments->get('foo'));
        $this->assertSame('string', (string) $arguments->get('foo')->type());
        $this->assertSame(['foo', 'bar', 'baz'], $arguments->get('foo')->toPrimitive());
    }

    public function testExtractEmptyStreamWhenNotFound()
    {
        $input = PackArgument::fromString(Str::of('...foo'));

        $arguments = $input->extract(
            new Map('string', 'mixed'),
            42,
            Stream::of('string', 'watev', 'foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(StreamInterface::class, $arguments->get('foo'));
        $this->assertSame('string', (string) $arguments->get('foo')->type());
        $this->assertSame([], $arguments->get('foo')->toPrimitive());
    }
}
