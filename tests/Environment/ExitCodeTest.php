<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\Environment\ExitCode;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ExitCodeTest extends TestCase
{
    use BlackBox;

    public function testToInt()
    {
        $this
            ->forAll(Set\Integers::between(0, 254))
            ->then(function(int $code): void {
                $this->assertSame($code, (new ExitCode($code))->toInt());
            });
    }

    public function testSuccessful()
    {
        $this->assertTrue((new ExitCode(0))->successful());
        $this
            ->forAll(Set\Integers::between(1, 254))
            ->then(function(int $code): void {
                $this->assertFalse((new ExitCode($code))->successful());
            });
    }
}
