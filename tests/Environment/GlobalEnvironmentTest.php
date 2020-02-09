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
use Innmind\Url\PathInterface;
use Innmind\Immutable\{
    StreamInterface,
    MapInterface
};
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

    public function testInput()
    {
        $this->assertInstanceOf(Readable::class, $this->env->input());
        $this->assertInstanceOf(Selectable::class, $this->env->input());
        $this->assertSame(STDIN, $this->env->input()->resource());
    }

    public function testOutput()
    {
        $this->assertInstanceOf(Writable::class, $this->env->output());
        $this->assertInstanceOf(Selectable::class, $this->env->output());
        $info = stream_get_meta_data($this->env->output()->resource());
        $this->assertSame('php://output', $info['uri']);
        $this->assertSame('wb', $info['mode']);
    }

    public function testError()
    {
        $this->assertInstanceOf(Writable::class, $this->env->error());
        $this->assertInstanceOf(Selectable::class, $this->env->error());
        $this->assertSame(STDERR, $this->env->error()->resource());
    }

    public function testArguments()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->env->arguments());
        $this->assertSame('string', (string) $this->env->arguments()->type());
        $this->assertSame($_SERVER['argv'], $this->env->arguments()->toPrimitive());
    }

    public function testVariables()
    {
        $this->assertInstanceOf(MapInterface::class, $this->env->variables());
        $this->assertSame('string', (string) $this->env->variables()->keyType());
        $this->assertSame('string', (string) $this->env->variables()->valueType());
        $this->assertSame(
            getenv(),
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
        $this->assertInstanceOf(PathInterface::class, $this->env->workingDirectory());
        $this->assertSame(getcwd(), (string) $this->env->workingDirectory());
    }
}
