# Reading the input

In some cases you want your cli app to be in the middle of a pipeline by feeding the input via `stdin`.

The example below will greet all the users:

```php title="greet.php"
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
        $buffer = Str::of('');

        do {
            [$read, $env] = $env->read();
            $buffer = $read->match(
                static fn($chunk) => $buffer->append($chunk->toString()),
                static fn() => $buffer,
            );

            if ($buffer->contains("\n")) {
                [$buffer, $env] = $buffer
                    ->split("\n")
                    ->match(
                        static fn($name, $buffer) => [
                            Str::of("\n")->join($buffer->map(fn($chunk) => $chunk->toString())),
                            $env->output(Str::of("Hello {$name->toString()}\n"))->unwrap(),
                        ],
                        static fn() => [
                            $buffer,
                            Attempt::result($env),
                        ],
                    );

                $outputFailure = $env->match(
                    static fn() => false,
                    static fn() => true,
                );

                if ($outputFailure) {
                    return $env;
                }
            }

        } while ($read->match(
            static fn() => true,
            static fn() => false,
        )); // stops when no more input

        return Attempt::result($env);
    }
};
```

You can test this via `cat list-of-names.txt | php greet.php`.
