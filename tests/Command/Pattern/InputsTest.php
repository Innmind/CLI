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
use PHPUnit\Framework\TestCase;

class InputsTest extends TestCase
{
    public function testLoad()
    {
        $inputs = new Inputs;

        $this->assertInstanceOf(RequiredArgument::class, $inputs->load(Str::of('foo')));
        $this->assertInstanceOf(OptionalArgument::class, $inputs->load(Str::of('[foo]')));
        $this->assertInstanceOf(PackArgument::class, $inputs->load(Str::of('...foo')));
        $this->assertInstanceOf(OptionFlag::class, $inputs->load(Str::of('-f|--foo')));
        $this->assertInstanceOf(OptionWithValue::class, $inputs->load(Str::of('-f|--foo=')));
    }

    public function testThrowWhenPatternNotRecognized()
    {
        $this->expectException(PatternNotRecognized::class);
        $this->expectExceptionMessage('_foo_');

        (new Inputs)->load(Str::of('_foo_'));
    }
}
