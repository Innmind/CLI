<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\CLI\{
    Commands,
    Command,
    Environment,
    Console,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
};
use PHPUnit\Framework\TestCase;

class CommandsTest extends TestCase
{
    public function testRunSingleCommand()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Console $console): Console
            {
                if (
                    !$console->arguments()->contains('container') ||
                    $console->arguments()->get('container') !== 'foo' ||
                    !$console->arguments()->contains('output') ||
                    $console->arguments()->get('output') !== 'bar' ||
                    !$console->options()->contains('foo')
                ) {
                    throw new \Exception;
                }

                return $console->exit(42);
            }

            public function usage(): string
            {
                return 'watch container [output] --foo';
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(42);
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testRunCommandByName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    if (
                        !$console->arguments()->contains('container') ||
                        $console->arguments()->get('container') !== 'foo' ||
                        !$console->arguments()->contains('output') ||
                        $console->arguments()->get('output') !== 'bar' ||
                        !$console->options()->contains('foo')
                    ) {
                        throw new \Exception;
                    }

                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch container [output] --foo';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->exactly(2))
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'watch', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(24);
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testRunCommandByNameEvenWhenAnotherCommandStartsWithTheSameName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'foobar';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->exactly(2))
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'foo'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(42);
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfItsName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'w'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(24);
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfTheSectionsOfItsName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo:bar:baz';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'f:b:b'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(42);
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testExitWhenCommandNotFound()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch container [output] --foo';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'bar'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->with(Str::of(" foo     \n watch   \n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testExitWhenMultipleCommandsMatchTheGivenName()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'bar';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'baz';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'ba'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->with(Str::of(" bar   \n baz   \n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testExitWhenCommandMisused()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Console $console): Console
            {
                return $console->exit(42);
            }

            public function usage(): string
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
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->with(Str::of('usage: bin/console watch container [output] --foo'."\n\nFoo\n\nBar\n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testEnvNotTemperedWhenCommandThrows()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Console $console): Console
            {
                throw new \Exception;
            }

            public function usage(): string
            {
                return 'watch container [output] --foo';
            }
        });
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'foo', '--foo', 'bar'));
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->method('variables')
            ->willReturn(Map::of());
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of('/'));

        $this->expectException(\Exception::class);

        $run($env);
    }

    public function testDisplayUsageWhenHelpOptionFound()
    {
        $run = new Commands(new class implements Command {
            public function __invoke(Console $console): Console
            {
                return $console->exit(42);
            }

            public function usage(): string
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
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', '--help'));
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->once())
            ->method('output')
            ->with(Str::of('usage: bin/console watch container [output] --foo'."\n\nFoo\n\nBar\n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testRunHelpCommand()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo'."\n\n".'Description';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch container [output] --foo'."\n\n".'Watch dependency injection';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console', 'help'));
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->once())
            ->method('output')
            ->with(Str::of(" foo    Description                \n watch  Watch dependency injection \n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }

    public function testDisplayHelpWhenNoCommandProvided()
    {
        $run = new Commands(
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(42);
                }

                public function usage(): string
                {
                    return 'foo';
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Console
                {
                    return $console->exit(24);
                }

                public function usage(): string
                {
                    return 'watch container [output] --foo';
                }
            },
        );
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::of('bin/console'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(64);
        $env
            ->expects($this->once())
            ->method('error')
            ->with(Str::of(" foo     \n watch   \n"))
            ->will($this->returnSelf());

        $this->assertInstanceOf(Environment::class, $run($env));
    }
}
