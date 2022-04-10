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
    Sequence,
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

            public function usage(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Sequence::of('string', 'foo'))
                ->toMapOf(
                    'string',
                    'string',
                    static function($name, $value) {
                        yield $name => $value;
                    },
                ),
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));

        $arguments = new Arguments(
            $spec
                ->pattern()
                ->arguments()
                ->extract(Sequence::of('string', 'foo', 'bar'))
                ->toMapOf(
                    'string',
                    'string',
                    static function($name, $value) {
                        yield $name => $value;
                    },
                ),
        );

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertTrue($arguments->contains('output'));
        $this->assertSame('bar', $arguments->get('output'));
    }

    public function testOf()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function usage(): string
            {
                return 'watch container --foo [output]';
            }
        });

        $arguments = Arguments::of(
            $spec,
            Sequence::of('string', 'foo'),
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
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, string>');

        new Arguments(Map::of('int', 'string'));
    }

    public function testThrowWhenInvalidArgumentsValues()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, string>');

        new Arguments(Map::of('string', 'mixed'));
    }

    public function testAccessPackByDedicatedMethod()
    {
        $arguments = new Arguments(
            null,
            $pack = Sequence::of('string', 'foo', 'bar'),
        );

        $this->assertFalse($arguments->contains('rest'));
        $this->assertSame($pack, $arguments->pack());
    }
}
