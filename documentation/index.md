---
hide:
    - navigation
---

# Getting started

CLI is a small library to wrap all the needed informations to build a command line tool. The idea to build this came while reading the [ponylang](https://www.ponylang.org/) [documentation](https://tutorial.ponylang.org/getting-started/how-it-works.html) realising that other languages use a similar approach for the entry point of the app, so I decided to have something similar for PHP.

The said approach is to have a `main` function as the starting point of execution of your code. This function has a the environment it runs in passed as argument so there's no need for global variables. However since not everything can be passed down as argument (it would complicate the interface), [ambient authority](https://en.wikipedia.org/wiki/Ambient_authority) can be exercised (as in regular PHP script).

!!! warning ""
    To correctly use this library you must validate your code with [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm)

## Installation

```sh
composer require innmind/cli
```

## Usage

To start a new CLI tool you need this boilerplate code:

```php title="cli.php"
declare(strict_types = 1);

require 'path/to/composer/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Str,
    Attempt,
};

new class extends Main {
    /**
     * @return Attempt<Environment>
     */
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        // Your code here

        return $env->output(Str::of("Hello world\n"));
    }
};
```

This will directly call the `main` function. The `$env` variable gives you access to the 3 standard streams (`stdin`, `stdout`, `stderr`), the list of arguments passed in the cli, all the environment variables, the working directory, if the terminal is interactive and a method to specify the exit code.
