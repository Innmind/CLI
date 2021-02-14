<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Environment;

use Innmind\CLI\{
    Environment\GlobalEnvironment,
    Environment\ExitCode,
    Environment
};
use Innmind\Stream\{
    Readable,
    Selectable,
    Writable
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class GlobalEnvironmentTest extends TestCase
{
    private $env;

    public function setUp(): void
    {
        $this->env = new GlobalEnvironment;
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

    public function testInput()
    {
        $this->assertInstanceOf(Readable::class, $this->env->input());
        $this->assertInstanceOf(Selectable::class, $this->env->input());
        $this->assertSame(\STDIN, $this->env->input()->resource());
    }

    public function testOutput()
    {
        $this->assertInstanceOf(Writable::class, $this->env->output());
        $this->assertInstanceOf(Selectable::class, $this->env->output());
        $info = \stream_get_meta_data($this->env->output()->resource());
        $this->assertSame('php://output', $info['uri']);
        $this->assertSame('wb', $info['mode']);
    }

    public function testError()
    {
        $this->assertInstanceOf(Writable::class, $this->env->error());
        $this->assertInstanceOf(Selectable::class, $this->env->error());
        $this->assertSame(\STDERR, $this->env->error()->resource());
    }

    public function testArguments()
    {
        $this->assertInstanceOf(Sequence::class, $this->env->arguments());
        $this->assertSame('string', (string) $this->env->arguments()->type());
        $this->assertSame($_SERVER['argv'], unwrap($this->env->arguments()));
    }

    public function testVariables()
    {
        $this->assertInstanceOf(Map::class, $this->env->variables());
        $this->assertSame('string', (string) $this->env->variables()->keyType());
        $this->assertSame('string', (string) $this->env->variables()->valueType());
        $this->assertSame(
            \getenv(),
            $this->env->variables()->reduce(
                [],
                static function(array $variables, string $key, string $value): array {
                    $variables[$key] = $value;

                    return $variables;
                }
            )
        );
    }

    public function testExitCode()
    {
        $this->assertInstanceOf(ExitCode::class, $this->env->exitCode());
        $this->assertSame(0, $this->env->exitCode()->toInt());
        $this->assertNull($this->env->exit(1));
        $this->assertSame(1, $this->env->exitCode()->toInt());
    }

    public function testWorkingDirectory()
    {
        $this->assertInstanceOf(Path::class, $this->env->workingDirectory());
        $this->assertTrue($this->env->workingDirectory()->directory());
        $this->assertSame(\getcwd().'/', $this->env->workingDirectory()->toString());
    }
}
