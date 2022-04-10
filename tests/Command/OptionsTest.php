<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Options,
    Command\Arguments,
    Command\Specification,
    Command,
    Environment,
};
use Innmind\Immutable\{
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testInterface()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function usage(): string
            {
                return 'watch container --foo --bar= [output]';
            }
        });

        $options = new Options(
            $spec
                ->pattern()
                ->options()
                ->extract(Sequence::of('--foo')),
        );

        $this->assertTrue($options->contains('foo'));
        $this->assertSame('', $options->get('foo'));
        $this->assertFalse($options->contains('bar'));

        $options = new Options(
            $spec
                ->pattern()
                ->options()
                ->extract(Sequence::of('--foo', '--bar=baz')),
        );

        $this->assertTrue($options->contains('foo'));
        $this->assertSame('', $options->get('foo'));
        $this->assertTrue($options->contains('bar'));
        $this->assertSame('baz', $options->get('bar'));
    }

    public function testOf()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function usage(): string
            {
                return 'watch container --foo --bar= [output]';
            }
        });

        $options = Options::of(
            $spec,
            Sequence::of('--foo'),
        );

        $this->assertInstanceOf(Options::class, $options);
        $this->assertTrue($options->contains('foo'));
        $this->assertSame('', $options->get('foo'));
        $this->assertFalse($options->contains('bar'));
    }

    public function testOptionsCanBeBuiltWithoutAnyValue()
    {
        $this->assertInstanceOf(Options::class, new Options);
    }
}
