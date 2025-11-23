<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use Innmind\CLI\{
    Commands,
    Command,
    Command\Usage,
    Environment,
    Console,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

#[Command\Name('foo')]
final class Foo implements Command
{
    public function __invoke(Console $console): Attempt
    {
        return Attempt::result($console->exit(42));
    }

    public function usage(): Usage
    {
        return Usage::for(self::class);
    }
}

class CommandsTest extends TestCase
{
    public function testRunSingleCommand()
    {
        $run = Commands::of(new class implements Command {
            public function __invoke(Console $console): Attempt
            {
                if (
                    !$console->arguments()->contains('container') ||
                    $console->arguments()->get('container') !== 'foo' ||
                    !$console->arguments()->contains('output') ||
                    $console->arguments()->get('output') !== 'bar' ||
                    !$console->options()->contains('foo')
                ) {
                    return Attempt::error(new \Exception);
                }

                return Attempt::result($console->exit(42));
            }

            public function usage(): Usage
            {
                return Usage::parse('watch container [output] --foo');
            }
        });
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandByName()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    if (
                        !$console->arguments()->contains('container') ||
                        $console->arguments()->get('container') !== 'foo' ||
                        !$console->arguments()->contains('output') ||
                        $console->arguments()->get('output') !== 'bar' ||
                        !$console->options()->contains('foo')
                    ) {
                        return Attempt::error(new \Exception);
                    }

                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'watch', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertSame(24, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandByNameSpecifiedAsAttribute()
    {
        $run = Commands::of(
            new Foo,
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    if (
                        !$console->arguments()->contains('container') ||
                        $console->arguments()->get('container') !== 'foo' ||
                        !$console->arguments()->contains('output') ||
                        $console->arguments()->get('output') !== 'bar' ||
                        !$console->options()->contains('foo')
                    ) {
                        return Attempt::error(new \Exception);
                    }

                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'watch', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertSame(24, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandByNameEvenWhenAnotherCommandStartsWithTheSameName()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foobar');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'foo'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfItsName()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'w'],
            [],
            '/',
        );

        $this->assertSame(24, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testRunCommandBySpecifyingOnlyTheStartOfTheSectionsOfItsName()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo:bar:baz');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'f:b:b'],
            [],
            '/',
        );

        $this->assertSame(42, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testExitWhenCommandNotFound()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'bar'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " foo    \n",
                " watch  \n",
            ],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'error')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testExitWhenMultipleCommandsMatchTheGivenName()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('bar');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('baz');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'ba'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " bar  \n",
                " baz  \n",
            ],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'error')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testExitWhenCommandMisused()
    {
        $run = Commands::of(new class implements Command {
            public function __invoke(Console $console): Attempt
            {
                return Attempt::result($console->exit(42));
            }

            public function usage(): Usage
            {
                return Usage::parse(<<<USAGE
watch container [output] --foo

Foo

Bar
USAGE);
            }
        });
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            ['usage: bin/console watch container [output] --foo --help --no-interaction'."\n\nFoo\n\nBar\n"],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'error')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testEnvNotTemperedWhenCommandThrows()
    {
        $run = Commands::of(new class implements Command {
            public function __invoke(Console $console): Attempt
            {
                return Attempt::error(new \Exception);
            }

            public function usage(): Usage
            {
                return Usage::parse('watch container [output] --foo');
            }
        });
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->expectException(\Exception::class);

        $run($env)->unwrap();
    }

    public function testDisplayUsageWhenHelpOptionFound()
    {
        $run = Commands::of(new class implements Command {
            public function __invoke(Console $console): Attempt
            {
                return Attempt::result($console->exit(42));
            }

            public function usage(): Usage
            {
                return Usage::parse(<<<USAGE
watch container [output] --foo

Foo

Bar
USAGE);
            }
        });
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', '--help'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            ['usage: bin/console watch container [output] --foo --help --no-interaction'."\n\nFoo\n\nBar\n"],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'output')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testRunHelpCommand()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo'."\n\n".'Description');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo'."\n\n".'Watch dependency injection');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'help'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " foo    Description\n",
                " watch  Watch dependency injection\n",
            ],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'output')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testDisplayHelpWhenNoCommandProvided()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::parse('foo');
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [" foo    \n", " watch  \n"],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'error')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testUsageForDoesntLoadTheFullUsageWhenCommandIsLoaded()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                }

                public function usage(): Usage
                {
                    return Usage::for(Foo::class)->load(
                        static fn() => throw new \Exception,
                    );
                }
            },
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::parse('watch container [output] --foo');
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'help'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                " foo    \n",
                " watch  \n",
            ],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'output')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testLazyLoadCommandUsage()
    {
        $run = Commands::of(
            new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                }

                public function usage(): Usage
                {
                    return Usage::for(Foo::class)->load(
                        static fn() => (new Foo)
                            ->usage()
                            ->argument('bar')
                            ->option('baz')
                            ->packArguments()
                            ->withDescription('whatever'),
                    );
                }
            },
        );
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', '--help'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();

        $this->assertNull($env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(
            [
                <<<USAGE
                usage: bin/console foo bar ...arguments --baz= --help --no-interaction

                whatever

                USAGE,
            ],
            $env
                ->outputted()
                ->filter(static fn($pair) => $pair[1] === 'output')
                ->map(static fn($pair) => $pair[0]->toString())
                ->toList(),
        );
    }

    public function testDoesntLazyLoadCommandWhenAnExactMatchHasBeenFound()
    {
        $loaded = false;
        $run = Commands::for(Sequence::lazy(static function() use (&$loaded) {
            yield new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::of('first');
                }
            };
            yield new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    return Usage::of('watch');
                }
            };
            $loaded = true;
            yield new class implements Command {
                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    return Usage::of('last');
                }
            };
        }));
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'watch', 'foo', '--foo', 'bar'],
            [],
            '/',
        );

        $this->assertFalse($loaded);
        $this->assertSame(24, $run($env)->unwrap()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testLazyLoadedCommandsAreLoadedOnceWhenSomeMatchesCommandName()
    {
        $loaded = 0;
        $run = Commands::for(Sequence::lazy(static function() use (&$loaded) {
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('bar');
                }
            };
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('foo');
                }
            };
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('baz');
                }
            };
        }));
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'ba'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();
        $this->assertSame(3, $loaded);
        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testLazyLoadedCommandsAreLoadedOnceWhenNoneMatchesCommandName()
    {
        $loaded = 0;
        $run = Commands::for(Sequence::lazy(static function() use (&$loaded) {
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('bar');
                }
            };
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(24));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('foo');
                }
            };
            yield new class($loaded) implements Command {
                public function __construct(private &$loaded)
                {
                }

                public function __invoke(Console $console): Attempt
                {
                    return Attempt::result($console->exit(42));
                }

                public function usage(): Usage
                {
                    ++$this->loaded;

                    return Usage::of('baz');
                }
            };
        }));
        $env = Environment::inMemory(
            [],
            true,
            ['bin/console', 'unknown'],
            [],
            '/',
        );

        $env = $run($env)->unwrap();
        $this->assertSame(3, $loaded);
        $this->assertSame(64, $env->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }
}
