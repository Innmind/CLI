<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\Environment\ExitCode;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class ExitCodeTest extends TestCase
{
    use BlackBox;

    public function testToInt(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::integers()->between(0, 254))
            ->prove(function(int $code): void {
                $this->assertSame($code, (new ExitCode($code))->toInt());
            });
    }

    public function testSuccessful()
    {
        $this->assertTrue((new ExitCode(0))->successful());
    }

    public function testNotSuccessful(): BlackBox\Proof
    {
        return $this
            ->forAll(Set\Integers::between(1, 254))
            ->prove(function(int $code): void {
                $this->assertFalse((new ExitCode($code))->successful());
            });
    }
}
