<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command\Pattern;

use Innmind\CLI\{
    Command\Usage,
    Command\Pattern\Inputs,
    Exception\PatternNotRecognized,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class InputsTest extends TestCase
{
    public function testThrowWhenPatternNotRecognized()
    {
        $this->expectException(PatternNotRecognized::class);
        $this->expectExceptionMessage('_foo_');

        $_ = (new Inputs)(Usage::of('name'), Str::of('_foo_'))->unwrap();
    }
}
