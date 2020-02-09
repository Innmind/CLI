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
    Sequence,
    Map,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class PatternTest extends TestCase
{
    private $pattern;

    public function setUp(): void
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
            Sequence::of('string', 'first', 'second')
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertSame('string', (string) $arguments->keyType());
        $this->assertSame('mixed', (string) $arguments->valueType());
        $this->assertCount(3, $arguments);
        $this->assertSame('first', $arguments->get('foo'));
        $this->assertSame('second', $arguments->get('bar'));
        $this->assertInstanceOf(Sequence::class, $arguments->get('foobar'));
        $this->assertSame('string', (string) $arguments->get('foobar')->type());
        $this->assertCount(0, $arguments->get('foobar'));
    }

    public function testOptions()
    {
        $options = $this->pattern->options();

        $this->assertInstanceOf(Pattern::class, $options);
        $this->assertNotSame($this->pattern, $options);
        $this->assertSame('--foo', (string) $options);
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            (string) $this->pattern
        );
    }

    public function testArguments()
    {
        $arguments = $this->pattern->arguments();

        $this->assertInstanceOf(Pattern::class, $arguments);
        $this->assertNotSame($this->pattern, $arguments);
        $this->assertSame('foo bar [baz] ...foobar', (string) $arguments);
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            (string) $this->pattern
        );
    }

    public function testClean()
    {
        $arguments = $this->pattern->options()->clean(
            Sequence::of('string', 'foo', '--foo', 'bar', 'baz')
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame('string', (string) $arguments->type());
        $this->assertSame(['foo', 'bar', 'baz'], unwrap($arguments));
    }
}
