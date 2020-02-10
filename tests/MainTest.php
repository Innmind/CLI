<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\Server\Control\{
    ServerFactory,
    Server\Command
};
use Innmind\Stream\Readable\Stream;
use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\Url\Path;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    private $processes;

    public function setUp(): void
    {
        $this->processes = ServerFactory::build()->processes();
    }

    public function testExit()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/exiter.php')
                    ->withArgument('10')
                    ->withWorkingDirectory(Path::of(getcwd()))
            );
        $process->wait();

        $this->assertSame(
            10,
            $process
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
                    ->withInput(Stream::ofContent('foobar'."\n".'baz'))
                    ->withWorkingDirectory(Path::of(getcwd()))
            );
        $process->wait();

        $this->assertSame(0, $process->exitCode()->toInt());
        $this->assertSame('foobar'."\n".'baz', $process->output()->toString());
    }

    public function testThrow()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/thrower.php')
                    ->withWorkingDirectory(Path::of(getcwd()))
            );
        $process->output()->foreach(function(Str $line, Type $type): void {
            $this->assertSame(Type::error(), $type);
        });
        $process->wait();

        $this->assertSame(1, $process->exitCode()->toInt());

        $cwd = getcwd();
        $output = Str::of($process->output()->toString())->split("\n");

        $this->assertCount(6, $output);
        $this->assertSame(
            "fixtures/thrower.php: Innmind\CLI\Exception\LogicException(waaat, 0)",
            $output->get(0)->toString()
        );
        $this->assertSame(
            "fixtures/thrower.php: $cwd/fixtures/thrower.php:18",
            $output->get(1)->toString()
        );
        $this->assertSame(
            "fixtures/thrower.php: ",
            $output->get(2)->toString()
        );
        $this->assertSame(
            "fixtures/thrower.php: class@anonymous",
            $output->get(3)->substring(0, 37)->toString()
        );
        $this->assertSame(
            "$cwd",
            $output->get(3)->substring(38, strlen($cwd))->toString()
        );
        $this->assertSame(
            "/fixtures/thrower.php",
            $output->get(3)->substring(38 + strlen($cwd), 21)->toString()
        );
        $this->assertRegExp(
            "~^->main\(\) at $cwd/src/Main.php:(48|39)$~",
            $output->get(3)->substring(-28 - strlen($cwd))->toString()
        );
        $this->assertSame(
            "fixtures/thrower.php: Innmind\CLI\Main->__construct() at $cwd/fixtures/thrower.php:15",
            $output->get(4)->toString()
        );
        $this->assertSame(
            '',
            $output->get(5)->toString()
        );
    }
}
