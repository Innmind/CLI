<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\CLI\{
    Commands,
    Command,
    Environment,
    Console,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class CommandsTest extends TestCase
{
    public function testRunSingleCommand()
    {
        $run = Commands::of(new class implements Command {
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandByName()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'watch', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertSame(24, $run($env)->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandByNameEvenWhenAnotherCommandStartsWithTheSameName()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'foo'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfItsName()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'w'],
            [],
            '/',
        );

        $this->assertSame(24, $run($env)->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfTheSectionsOfItsName()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'f:b:b'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testExitWhenCommandNotFound()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'bar'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " foo    \n",
                " watch  \n",
            ],
            $env->errors(),
        );
    }

    public function testExitWhenMultipleCommandsMatchTheGivenName()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'ba'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " bar  \n",
                " baz  \n",
            ],
            $env->errors(),
        );
    }

    public function testExitWhenCommandMisused()
    {
        $run = Commands::of(new class implements Command {
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            ['usage: bin/console watch container [output] --foo --help --no-interaction'."\n\nFoo\n\nBar\n"],
            $env->errors(),
        );
    }

    public function testEnvNotTemperedWhenCommandThrows()
    {
        $run = Commands::of(new class implements Command {
            public function __invoke(Console $console): Console
            {
                throw new \Exception;
            }

            public function usage(): string
            {
                return 'watch container [output] --foo';
            }
        });
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->expectException(\Exception::class);

        $run($env);
    }

    public function testDisplayUsageWhenHelpOptionFound()
    {
        $run = Commands::of(new class implements Command {
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', '--help'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            ['usage: bin/console watch container [output] --foo --help --no-interaction'."\n\nFoo\n\nBar\n"],
            $env->outputs(),
        );
    }

    public function testRunHelpCommand()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console', 'help'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " foo    Description\n",
                " watch  Watch dependency injection\n",
            ],
            $env->outputs(),
        );
    }

    public function testDisplayHelpWhenNoCommandProvided()
    {
        $run = Commands::of(
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
        $env = Environment\InMemory::of(
            [],
            true,
            ['bin/console'],
            [],
            '/',
        );

        $env = $run($env);

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [" foo    \n", " watch  \n"],
            $env->errors(),
        );
    }
}
