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
use Innmind\Immutable\Stream;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testInterface()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args, Options $options): void
            {
            }

            public function __toString(): string
            {
                return 'watch container --foo --bar= [output]';
            }
        });

        $options = new Options($spec, Stream::of('string', '--foo'));

        $this->assertTrue($options->contains('foo'));
        $this->assertTrue($options->get('foo'));
        $this->assertFalse($options->contains('bar'));

        $options = new Options($spec, Stream::of('string', '--foo', '--bar=baz'));

        $this->assertTrue($options->contains('foo'));
        $this->assertTrue($options->get('foo'));
        $this->assertTrue($options->contains('bar'));
        $this->assertSame('baz', $options->get('bar'));
    }
}
