<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Usage,
    Command\Pattern,
};
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PatternTest extends TestCase
{
    private $pattern;

    public function setUp(): void
    {
        $this->pattern = Usage::of('name')
            ->argument('foo')
            ->argument('bar')
            ->optionalArgument('baz')
            ->packArguments()
            ->flag('foo')
            ->pattern();
    }

    public function testStringCast()
    {
        $this->assertSame(
            'foo bar [baz] ...arguments --foo',
            $this->pattern->toString(),
        );
    }

    public function testDoesntThrowWhenNoPackArgument()
    {
        $this->assertInstanceOf(
            Pattern::class,
            Usage::of('name')->pattern(),
        );
    }

    public function testThrowWhenRequirementArgumentFoundAfterAnOptionalOne()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No required argument after an optional one');

        Usage::of('name')
            ->argument('baz')
            ->optionalArgument('foo')
            ->flag('foo')
            ->argument('bar');
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
