<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment\GlobalEnvironment,
    Environment,
};
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Maybe,
};
use PHPUnit\Framework\TestCase;

class GlobalEnvironmentTest extends TestCase
{
    private $env;

    public function setUp(): void
    {
        $this->env = GlobalEnvironment::of(IO::fromAmbientAuthority());
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Environment::class, $this->env);
    }

    public function testInteractive()
    {
        // can't prove via a test that the env can be interactive as tests are
        // always run in an non interactive env
        $this->assertFalse($this->env->interactive());
    }

    public function testArguments()
    {
        $this->assertInstanceOf(Sequence::class, $this->env->arguments());
        $this->assertSame($_SERVER['argv'], $this->env->arguments()->toList());
    }

    public function testVariables()
    {
        $this->assertInstanceOf(Map::class, $this->env->variables());
        $this->assertSame(
            \getenv(),
            $this->env->variables()->reduce(
                [],
                static function(array $variables, string $key, string $value): array {
                    $variables[$key] = $value;

                    return $variables;
                },
            ),
        );
    }

    public function testExitCode()
    {
        $this->assertEquals(Maybe::nothing(), $this->env->exitCode());
        $env = $this->env->exit(1);
        $this->assertInstanceOf(Environment::class, $env);
        $this->assertSame(1, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testWorkingDirectory()
    {
        $this->assertInstanceOf(Path::class, $this->env->workingDirectory());
        $this->assertTrue($this->env->workingDirectory()->directory());
        $this->assertSame(\getcwd().'/', $this->env->workingDirectory()->toString());
    }
}
