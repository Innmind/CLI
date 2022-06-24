<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\Command\Options;
use Innmind\Immutable\{
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testInterface()
    {
        $options = new Options(Map::of(['foo', '']));

        $this->assertTrue($options->contains('foo'));
        $this->assertSame('', $options->get('foo'));
        $this->assertFalse($options->contains('bar'));

        $options = new Options(Map::of(['foo', ''], ['bar', 'baz']));

        $this->assertTrue($options->contains('foo'));
        $this->assertSame('', $options->get('foo'));
        $this->assertTrue($options->contains('bar'));
        $this->assertSame('baz', $options->get('bar'));
    }

    public function testOptionsCanBeBuiltWithoutAnyValue()
    {
        $this->assertInstanceOf(Options::class, new Options);
    }
}
