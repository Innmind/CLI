<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Pattern,
    Exception\OnlyOnePackArgumentAllowed,
    Exception\PackArgumentMustBeTheLastOne,
    Exception\NoRequiredArgumentAllowedAfterAnOptionalOne,
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream,
    MapInterface,
};
use PHPUnit\Framework\TestCase;

class PatternTest extends TestCase
{
    private $pattern;

    public function setUp()
    {
        $this->pattern = new Pattern(
            Str::of('foo'),
            Str::of('bar'),
            Str::of('[baz]'),
            Str::of('...foobar'),
            Str::of('--foo')
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            (string) $this->pattern
        );
    }

    public function testDoesntThrowWhenNoPackArgument()
    {
        $this->assertInstanceOf(
            Pattern::class,
            new Pattern
        );
    }

    public function testThrowWhenMoreThanOnePackArgument()
    {
        $this->expectException(OnlyOnePackArgumentAllowed::class);

        new Pattern(
            Str::of('...foo'),
            Str::of('...bar'),
            Str::of('--foo')
        );
    }

    public function testThrowWhenPackArgumentIsNotTheLastOne()
    {
        $this->expectException(PackArgumentMustBeTheLastOne::class);

        new Pattern(
            Str::of('...foo'),
            Str::of('bar'),
            Str::of('--foo')
        );
    }

    public function testThrowWhenRequirementArgumentFoundAfterAnOptionalOne()
    {
        $this->expectException(NoRequiredArgumentAllowedAfterAnOptionalOne::class);

        new Pattern(
            Str::of('baz'),
            Str::of('[foo]'),
            Str::of('--foo'),
            Str::of('bar')
        );
    }

    public function testExtract()
    {
        $arguments = $this->pattern->extract(
            Stream::of('string', 'first', 'second')
        );

        $this->assertInstanceOf(MapInterface::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(3, $arguments);
        $this->assertSame('first', $arguments->get('foo'));
        $this->assertSame('second', $arguments->get('bar'));
        $this->assertInstanceOf(StreamInterface::class, $arguments->get('foobar'));
        $this->assertSame('string', (string) $arguments->get('foobar')->type());
        $this->assertCount(0, $arguments->get('foobar'));
    }
}
