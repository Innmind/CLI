# CLI

[![Build Status](https://github.com/Innmind/CLI/workflows/CI/badge.svg)](https://github.com/Innmind/CLI/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/CLI/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/CLI)
[![Type Coverage](https://shepherd.dev/github/Innmind/CLI/coverage.svg)](https://shepherd.dev/github/Innmind/CLI)

CLI is a small library to wrap all the needed informations to build a command line tool. The idea to build this came while reading the [ponylang](https://www.ponylang.org/) [documentation](https://tutorial.ponylang.org/getting-started/how-it-works.html) realising that other languages use a similar approach for the entry point of the app, so I decided to have a something similar for PHP.

The said approach is to have a `main` function as the starting point of execution of your code. This function has a the environment it runs in passed as argument so there's no need for global variables. However since not everything can be passed down as argument (it would complicate the interface), [ambient authority](https://en.wikipedia.org/wiki/Ambient_authority) can be exercised (as in regular PHP script).

## Installation

```sh
composer require innmind/cli
```

## Usage

To start a new CLI tool you need this boilerplate code:

```php
# cli.php
<?php
declare(strict_types = 1);

require 'path/to/composer/autoload.php'

use Innmind\CLI\{
    Main,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        //your code here
    }
}
```

This will directly call the `main` function. The `$env` variable gives you access to the 3 standard streams (`stdin`, `stdout`, `stderr`), the list of arguments passed in the cli, all the environment variables, the working directory and a method to specify the exit code.

**Note**: Calling `$env->exit(1)` will _not_ exit directly your program, you _must_ call `return;` in order to make the `main` function to stop.

## Commands

Using directly the `main` function is enough when building simple tools, but you often want to provide multiple commands in the same tool (associated with arguments/options validation). This library provides a way to do that. Here's an example:

```php
use Innmind\CLI\{
    Commands,
    Command,
    Command\Arguments,
    Command\Options,
};

function main(Environment $env, OperatingSystem $os): void
{
    $run = new Commands(
        new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): env
            {
                //your code here
            }

            public function __toString(): string
            {
                return 'foo';
            }
        }
    );
    $run($env);
}
```

In your terminal you would call this command like this `php cli.php foo`. But since here a single command is defined you could simply call `php cli.php`. Of course you can define as many commands as you wish.

Here the command is an anonymous class to simplify the example, but it can be a normal class implementing `Command`. Since the command simply needs to implement an interface, you have full control of the dependencies you can inject into it. Meaning your commands instances can comme from a dependency injection container. The other advantage since the interface is simple is that you can easily unit test your commands.

The `Command` interface requires you to implement 2 methods: `__invoke` and `__toString`. The first one is the one that will be called if it's the desired command to be called. `__toString` is the place where you define the _structure_ of your command, by that I mean the name of the command, the list of its arguments/options, its short description and full description.

To define all properties of your command it would ressemble to this:

```
{command name} {optional list of arguments/options}

{short description on a single line}

{full description that can span multiple lines}
```

Command name and short description are displayed when you run the `help` command (that will list all available commands). The list of arguments/options and full description are displayed only when you call the command with the extra `--help` option (or you misused the command).

To define arguments you have access to 3 patterns:

* `foo` with this you ask for a required argument that will be accessed like so `$arguments->get('foo')`
* `[bar]` with this you ask for an optional argument, you must verify its presence via `$arguments->contains('bar')` before accessing it. Optional arguments can't be followed by required ones
* `...baz` with this you ask that all extra arguments will be regrouped as a list with the name `baz`, it will provide a `Stream<string>` of any length. You can only have one argument of this type and must be the last one

To define options you have access to 2 patterns:

* `-f|--foo` this defines a flag that can be called by `-f` or `--foo`, if you don't want a short name simply define `--foo`
* `-f|--foo=` this defines an option that requires a value, if you don't want a short name simply define `--foo=`. It can be called via `-f=value`, `-f value` or `--foo=value`
