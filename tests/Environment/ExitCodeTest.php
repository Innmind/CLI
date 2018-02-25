<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\Environment\ExitCode;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class ExitCodeTest extends TestCase
{
    use TestTrait;

    public function testToInt()
    {
        $this
            ->forAll(Generator\choose(0, 254))
            ->then(function(int $code): void {
                $this->assertSame($code, (new ExitCode($code))->toInt());
            });
    }

    public function testSuccessful()
    {
        $this->assertTrue((new ExitCode(0))->successful());
        $this
            ->forAll(Generator\choose(1, 254))
            ->then(function(int $code): void {
                $this->assertFalse((new ExitCode($code))->successful());
            });
    }

    public function testNegativeCodesAreReplacedByZero()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $code): void {
                $this->assertSame(0, (new ExitCode($code))->toInt());
            });
    }

    public function testCodesHigherThan254AreReplacedBy254()
    {
        $this
            ->forAll(Generator\choose(255, 10000))
            ->then(function(int $code): void {
                $this->assertSame(254, (new ExitCode($code))->toInt());
            });
    }
}
