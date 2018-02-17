<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\Server\Control\{
    ServerFactory,
    Server\Command
};
use Innmind\Filesystem\Stream\StringStream;
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
}
