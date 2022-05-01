<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Arguments,
    Command\Specification,
    Command,
    Console,
};
use Innmind\Immutable\{
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testInterface()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Console $console): Console
            {
            }

            public function usage(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Sequence::of('foo')),
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Sequence::of('foo', 'bar')),
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertTrue($arguments->contains('output'));
        $this->assertSame('bar', $arguments->get('output'));
    }

    public function testOf()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Console $console): Console
            {
            }

            public function usage(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = Arguments::of(
            $spec,
            Sequence::of('foo'),
        );

        $this->assertInstanceOf(Arguments::class, $arguments);
        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));
    }

    public function testArgumentsCanBeBuiltWithoutAnyValue()
    {
        $this->assertInstanceOf(Arguments::class, new Arguments);
    }

    public function testAccessPackByDedicatedMethod()
    {
        $arguments = new Arguments(
            null,
            $pack = Sequence::of('foo', 'bar'),
        );

        $this->assertFalse($arguments->contains('rest'));
        $this->assertSame($pack, $arguments->pack());
    }
}
