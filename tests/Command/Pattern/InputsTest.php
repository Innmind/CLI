<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Pattern\Inputs,
    Command\Pattern\RequiredArgument,
    Command\Pattern\OptionalArgument,
    Command\Pattern\PackArgument,
    Command\Pattern\OptionFlag,
    Command\Pattern\OptionWithValue,
    Exception\PatternNotRecognized,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class InputsTest extends TestCase
{
    public function testLoad()
    {
        $inputs = new Inputs;

        $this->assertInstanceOf(RequiredArgument::class, $inputs(Str::of('foo')));
        $this->assertInstanceOf(OptionalArgument::class, $inputs(Str::of('[foo]')));
        $this->assertInstanceOf(PackArgument::class, $inputs(Str::of('...foo')));
        $this->assertInstanceOf(OptionFlag::class, $inputs(Str::of('-f|--foo')));
        $this->assertInstanceOf(OptionWithValue::class, $inputs(Str::of('-f|--foo=')));
    }

    public function testThrowWhenPatternNotRecognized()
    {
        $this->expectException(PatternNotRecognized::class);
        $this->expectExceptionMessage('_foo_');

        (new Inputs)(Str::of('_foo_'));
    }
}
