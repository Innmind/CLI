# Commands

Commands are a way to define the arguments and options the user can provide to interact with your tool.

## Single command tool

Some tools are so simple that it provides a single command. You can build one like this:

```php
# Greet.php
declare(strict_types = 1);

use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Immutable\Str;

final class Greet implements Command
{
    public function __invoke(Console $console): Console
    {
        return $console->output(
            Str::of('Hi ')
                ->append($console->arguments()->get('name'))
                ->append("\n"),
        );
    }

    public function usage(): string
    {
        return 'greet name';
    }
}
```

```php
# cli.php
declare(strict_types = 1);

require 'path/to/composer/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): Environment
    {
        $run = Commands::of(new Greet);

        return $run($env);
    }
};
```

You can run this with `php cli.php greet Bob` and it will print `Hi Bob`. And since it's a single command tool you can omit the `greet` and run it with `php cli.php Bob` and it will print the same thing.

## Multi commands tool

For more complex tools you'll want to provide mutiple commands to the user. The process is the same as the example above but you only need to provide multiple commands to the `Commands` object like so:

```php
# cli.php
declare(strict_types = 1);

require 'path/to/composer/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Commands,
};
use Innmind\OperatingSystem\OperatingSystem;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): Environment
    {
        $run = Commands::of(new Command1, new Command2, new Etc);

        return $run($env);
    }
};
```

In this case however you always need to provide the name of the command you want to run.

## Declaring a command usage

The `usage` method of a command is the way to declare the name, the arguments/options and the descriptions of the command. It's always formatted like this:

```
{command-name} {list of arguments and options}

{Optional short description}

{Optional long description}
```

The short description is displayed when listing all the commands (via `php cli.php --help`).

The long description is displayed when asking for help on a specific command (via `php cli.php command-name --help`).

Here are all the syntax to declare arguments and options:
- `argument-name` in plain text means it's a required argument, access it via `$console->arguments()->get('argument-name')`
- `[argument-name]` means it's an optional argument, access it via `$console->arguments()->maybe('argument-name')`
- `...arguments` means it's a variadic argument, you can only declare one as a last argument and access it via `$console->arguments()->pack()`
- `-f|--flag` declares an option that cannot have a value, access it via `$console->options()->contains('flag')`
- `-o|--option=` declares an option that can have a value, access it via `$console->options()->maybe('option')`
