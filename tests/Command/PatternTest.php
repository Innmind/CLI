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
            Str::of('--foo'),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            $this->pattern->toString(),
        );
    }

    public function testDoesntThrowWhenNoPackArgument()
    {
        $this->assertInstanceOf(
            Pattern::class,
            new Pattern,
        );
    }

    public function testThrowWhenMoreThanOnePackArgument()
    {
        $this->expectException(OnlyOnePackArgumentAllowed::class);

        new Pattern(
            Str::of('...foo'),
            Str::of('...bar'),
            Str::of('--foo'),
        );
    }

    public function testThrowWhenPackArgumentIsNotTheLastOne()
    {
        $this->expectException(PackArgumentMustBeTheLastOne::class);

        new Pattern(
            Str::of('...foo'),
            Str::of('bar'),
            Str::of('--foo'),
        );
    }

    public function testThrowWhenRequirementArgumentFoundAfterAnOptionalOne()
    {
        $this->expectException(NoRequiredArgumentAllowedAfterAnOptionalOne::class);

        new Pattern(
            Str::of('baz'),
            Str::of('[foo]'),
            Str::of('--foo'),
            Str::of('bar'),
        );
    }

    public function testExtract()
    {
        $arguments = $this->pattern->extract(
            Sequence::of('first', 'second'),
        );

        $this->assertInstanceOf(Map::class, $arguments);
        $this->assertCount(3, $arguments);
        $this->assertSame('first', $arguments->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('second', $arguments->get('bar')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertInstanceOf(Sequence::class, $arguments->get('foobar')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(0, $arguments->get('foobar')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testOptions()
    {
        $options = $this->pattern->options();

        $this->assertInstanceOf(Pattern::class, $options);
        $this->assertNotSame($this->pattern, $options);
        $this->assertSame('--foo', $options->toString());
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            $this->pattern->toString(),
        );
    }

    public function testArguments()
    {
        $arguments = $this->pattern->arguments();

        $this->assertInstanceOf(Pattern::class, $arguments);
        $this->assertNotSame($this->pattern, $arguments);
        $this->assertSame('foo bar [baz] ...foobar', $arguments->toString());
        $this->assertSame(
            'foo bar [baz] ...foobar --foo',
            $this->pattern->toString(),
        );
    }

    public function testClean()
    {
        $arguments = $this->pattern->options()->clean(
            Sequence::of('foo', '--foo', 'bar', 'baz'),
        );

        $this->assertInstanceOf(Sequence::class, $arguments);
        $this->assertSame(['foo', 'bar', 'baz'], $arguments->toList());
    }
}
