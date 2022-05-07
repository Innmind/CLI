<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\Command\Arguments;
use Innmind\Immutable\{
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testInterface()
    {
        $arguments = new Arguments(Map::of(['container', 'foo']));

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertFalse($arguments->contains('output'));

        $arguments = new Arguments(Map::of(['container', 'foo'], ['output', 'bar']));

        $this->assertTrue($arguments->contains('container'));
        $this->assertSame('foo', $arguments->get('container'));
        $this->assertTrue($arguments->contains('output'));
        $this->assertSame('bar', $arguments->get('output'));
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
