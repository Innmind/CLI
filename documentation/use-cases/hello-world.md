# Hello world

This is the simplest example to write a cli tool.

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
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        return $env->output(Str::of("Hello world\n"));
    }
};
```

## Greeting someone by their name

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
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        return $env
            ->arguments()
            ->get(1) // not zero because it's the cli name
            ->match(
                static fn($name) => $env->output(Str::of("Hello $name\n")),
                static fn() => $env->error(Str::of("Sorry, I didn't catch your name\n")),
            );
    }
};
```

You can run it via `php cli.php John` or `php cli.php` to have the error message.

## Ask for the name interactively

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
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        $question = Question::of("What's your name?");

        return $question($env)->flatMap(static function($response) {
            [$response, $env] = $response;

            return $response->match(
                static fn($name) => $env->output(Str::of("Hello $name\n")),
                static fn() => $env->error(Str::of("Sorry, I didn't catch your name\n")),
            );
        });
    }
};
```
