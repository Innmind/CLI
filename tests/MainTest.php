<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\Server\Command;
use Innmind\Filesystem\File\Content;
use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\Url\Path;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    private $processes;

    public function setUp(): void
    {
        $this->processes = Factory::build()->control()->processes();
    }

    public function testExit()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/exiter.php')
                    ->withArgument('10')
                    ->withWorkingDirectory(Path::of(\getcwd()))
                    ->withEnvironment('PATH', $_SERVER['PATH']),
            );
        $exitCode = $process->wait()->match(
            static fn() => null,
            static fn($e) => $e->exitCode()->toInt(),
        );

        $this->assertSame(10, $exitCode);
    }

    public function testEcho()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/echo.php')
                    ->withArgument('10')
                    ->withInput(Content\Lines::ofContent('foobar'."\n".'baz'))
                    ->withWorkingDirectory(Path::of(\getcwd()))
                    ->withEnvironment('PATH', $_SERVER['PATH']),
            );

        $this->assertSame('foobar'."\n".'baz', $process->output()->toString());
    }

    public function testThrow()
    {
        $process = $this
            ->processes
            ->execute(
                Command::foreground('php')
                    ->withArgument('fixtures/thrower.php')
                    ->withWorkingDirectory(Path::of(\getcwd()))
                    ->withEnvironment('PATH', $_SERVER['PATH']),
            );
        $process->output()->foreach(function(Str $line, Type $type): void {
            $this->assertSame(Type::error, $type);
        });

        $cwd = \getcwd();
        $output = Str::of($process->output()->toString())->split("\n")->toList();

        $this->assertCount(6, $output);
        $this->assertSame(
            "Innmind\CLI\Exception\LogicException(waaat, 0)",
            $output[0]->toString(),
        );
        $this->assertSame(
            "$cwd/fixtures/thrower.php:18",
            $output[1]->toString(),
        );
        $this->assertSame(
            '',
            $output[2]->toString(),
        );
        $this->assertSame(
            'Innmind\CLI\Main@anonymous',
            $output[3]->substring(0, 26)->toString(),
        );
        $this->assertSame(
            "$cwd",
            $output[3]->substring(27, \strlen($cwd))->toString(),
        );
        $this->assertSame(
            '/fixtures/thrower.php',
            $output[3]->substring(27 + \strlen($cwd), 21)->toString(),
        );
        $this->assertMatchesRegularExpression(
            "~^->main\(\) at $cwd/src/Main.php:(37|28)$~",
            $output[3]->substring(-28 - \strlen($cwd))->toString(),
        );
        $this->assertSame(
            "Innmind\CLI\Main->__construct() at $cwd/fixtures/thrower.php:15",
            $output[4]->toString(),
        );
        $this->assertSame(
            '',
            $output[5]->toString(),
        );
    }
}
