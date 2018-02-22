<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Arguments,
    Command\Options,
    Command\Specification,
    Command,
    Environment,
};
use Innmind\Immutable\{
    Stream,
    Map,
};
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testInterface()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function __toString(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Stream::of('string', 'foo'))
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Stream::of('string', 'foo', 'bar'))
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertTrue($arguments->contains('output'));
        $this->assertSame('bar', $arguments->get('output'));
    }

    public function testFromSpecification()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function __toString(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = Arguments::fromSpecification(
            $spec,
            Stream::of('string', 'foo')
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

    public function testThrowWhenInvalidArgumentsKeys()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type MapInterface<string, mixed>');

        new Arguments(new Map('int', 'mixed'));
    }

    public function testThrowWhenInvalidArgumentsValues()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type MapInterface<string, mixed>');

        new Arguments(new Map('string', 'string'));
    }
}
