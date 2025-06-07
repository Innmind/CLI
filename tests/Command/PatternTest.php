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
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

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

    public function testParse()
    {
        [$arguments] = ($this->pattern)(
            Sequence::of('first', 'second'),
        );

        $this->assertSame('first', $arguments->get('foo'));
        $this->assertSame('second', $arguments->get('bar'));
        $this->assertCount(0, $arguments->pack());
    }
}
