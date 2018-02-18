<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Arguments,
    Command\Specification,
    Command,
    Environment,
};
use Innmind\Immutable\Stream;
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testInterface()
    {
        $spec = new Specification(new class implements Command {
            public function __invoke(Environment $env, Arguments $args): void
            {
            }

            public function __toString(): string
            {
                return 'watch container [output]';
            }
        });

        $arguments = new Arguments($spec, Stream::of('string', 'foo'));

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));

        $arguments = new Arguments($spec, Stream::of('string', 'foo', 'bar'));

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertTrue($arguments->contains('output'));
        $this->assertSame('bar', $arguments->get('output'));
    }
}
