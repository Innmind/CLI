<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\Server\Control\{
    ServerFactory,
    Server\Command
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    private $processes;

    public function setUp()
    {
        $this->processes = (new ServerFactory)
            ->make()
            ->processes();
    }

    public function testExit()
    {
        $this->assertSame(
            10,
            $this
                ->processes
                ->execute(
                    Command::foreground('php')
                        ->withArgument('fixtures/exiter.php')
                        ->withArgument('10')
                        ->withWorkingDirectory(getcwd())
                )
                ->wait()
                ->exitCode()
                ->toInt()
        );
    }

    public function testEcho()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/echo.php')
                    ->withArgument('10')
                    ->withInput(new StringStream('foobar'."\n".'baz'))
                    ->withWorkingDirectory(getcwd())
            )
            ->wait();

        $this->assertSame(0, $process->exitCode()->toInt());
        $this->assertSame('foobar'."\n".'baz', (string) $process->output());
    }

    public function testThrow()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/thrower.php')
                    ->withWorkingDirectory(getcwd())
            );
        $process->output()->foreach(function(Str $line, Type $type): void {
            $this->assertSame(Type::error(), $type);
        });
        $process->wait();

        $this->assertSame(1, $process->exitCode()->toInt());

        $cwd = getcwd();
        $output = Str::of((string) $process->output())->split("\n");

        $this->assertCount(6, $output);
        $this->assertSame(
            "fixtures/thrower.php: Innmind\CLI\Exception\LogicException(waaat, 0)",
            (string) $output->get(0)
        );
        $this->assertSame(
            "fixtures/thrower.php: $cwd/fixtures/thrower.php:18",
            (string) $output->get(1)
        );
        $this->assertSame(
            "fixtures/thrower.php: ",
            (string) $output->get(2)
        );
        $this->assertSame(
            "fixtures/thrower.php: class@anonymous",
            (string) $output->get(3)->substring(0, 37)
        );
        $this->assertSame(
            "$cwd",
            (string) $output->get(3)->substring(38, strlen($cwd))
        );
        $this->assertSame(
            "/fixtures/thrower.php",
            (string) $output->get(3)->substring(38 + strlen($cwd), 21)
        );
        $this->assertRegExp(
            "~^->main\(\) at $cwd/src/Main.php:(39|32)$~",
            (string) $output->get(3)->substring(-28 - strlen($cwd))
        );
        $this->assertSame(
            "fixtures/thrower.php: Innmind\CLI\Main->__construct() at $cwd/fixtures/thrower.php:15",
            (string) $output->get(4)
        );
        $this->assertSame(
            '',
            (string) $output->get(5)
        );
    }
}
