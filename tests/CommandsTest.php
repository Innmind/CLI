<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\CLI\{
    Commands,
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Stream,
    Str,
};
use PHPUnit\Framework\TestCase;

class CommandsTest extends TestCase
{
    public function testRunSingleCommand()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                if (
                    !$arguments->contains('container') ||
                    $arguments->get('container') !== 'foo' ||
                    !$arguments->contains('output') ||
                    $arguments->get('output') !== 'bar' ||
                    !$options->contains('foo')
                ) {
                    throw new \Exception;
                }

                $env->exit(42);
            }

            public function __toString(): string
            {
                return 'watch container [output] --foo';
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(42);

        $this->assertNull($run($env));
    }

    public function testRunCommandByName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(42);
                }

                public function __toString(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    if (
                        !$arguments->contains('container') ||
                        $arguments->get('container') !== 'foo' ||
                        !$arguments->contains('output') ||
                        $arguments->get('output') !== 'bar' ||
                        !$options->contains('foo')
                    ) {
                        throw new \Exception;
                    }

                    $env->exit(24);
                }

                public function __toString(): string
                {
                    return 'watch container [output] --foo';
                }
            }
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->exactly(2))
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', 'watch', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(24);

        $this->assertNull($run($env));
    }

    public function testExitWhenCommandNotFound()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(42);
                }

                public function __toString(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(24);
                }

                public function __toString(): string
                {
                    return 'watch container [output] --foo';
                }
            }
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn ($output = $this->createMock(Writable::class));
        $output
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === " foo     \n watch   ";
            }))
            ->will($this->returnSelf());
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === "\n";
            }))
            ->will($this->returnSelf());

        $this->assertNull($run($env));
    }

    public function testExitWhenCommandMisused()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                $env->exit(42);
            }

            public function __toString(): string
            {
                return <<<USAGE
watch container [output] --foo

Foo

Bar
USAGE;
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->exactly(2))
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn ($output = $this->createMock(Writable::class));
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === 'usage: bin/console watch container [output] --foo'."\n\nFoo\n\nBar\n";
            }));

        $this->assertNull($run($env));
    }

    public function testEnvNotTemperedWhenCommandThrows()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                throw new \Exception;
            }

            public function __toString(): string
            {
                return 'watch container [output] --foo';
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->never())
            ->method('exit');

        $this->expectException(\Exception::class);

        $run($env);
    }

    public function testDisplayUsageWhenHelpOptionFound()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
                $env->exit(42);
            }

            public function __toString(): string
            {
                return <<<USAGE
watch container [output] --foo

Foo

Bar
USAGE;
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->exactly(2))
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', '--help'));
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->once())
            ->method('output')
            ->willReturn ($output = $this->createMock(Writable::class));
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === 'usage: bin/console watch container [output] --foo'."\n\nFoo\n\nBar\n";
            }));

        $this->assertNull($run($env));
    }

    public function testRunHelpCommand()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(42);
                }

                public function __toString(): string
                {
                    return 'foo'."\n\n".'Description';
                }
            },
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(24);
                }

                public function __toString(): string
                {
                    return 'watch container [output] --foo'."\n\n".'Watch dependency injection';
                }
            }
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console', 'help'));
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->once())
            ->method('output')
            ->willReturn ($output = $this->createMock(Writable::class));
        $output
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === " foo    Description                \n watch  Watch dependency injection ";
            }))
            ->will($this->returnSelf());
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === "\n";
            }))
            ->will($this->returnSelf());

        $this->assertNull($run($env));
    }

    public function testDisplayHelpWhenNoCommandProvided()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(42);
                }

                public function __toString(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Environment $env, Arguments $arguments, Options $options): void
                {
                    $env->exit(24);
                }

                public function __toString(): string
                {
                    return 'watch container [output] --foo';
                }
            }
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Stream::of('string', 'bin/console'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn ($output = $this->createMock(Writable::class));
        $output
            ->expects($this->at(0))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === " foo     \n watch   ";
            }))
            ->will($this->returnSelf());
        $output
            ->expects($this->at(1))
            ->method('write')
            ->with($this->callback(function(Str $value): bool {
                return (string) $value === "\n";
            }))
            ->will($this->returnSelf());

        $this->assertNull($run($env));
    }
}
