---
hide:
    - navigation
    - toc
---

# Testing

To ease the process of testing your [commands](use-cases/commands.md) this package provides an implementation of `Environment` just for this case. This implementation has the same behaviour as the one provided provided when running your tool.

You can write such a test like this:

```php
use Innmind\CLI\{
    Commands,
    Environment,
};
use PHPUnit\Framework\TestCase;

class GreetTest extends TestCase
{
    public function testCommandGreetsTheUser()
    {
        $commands = Commands::of(new Greet);
        $environment = $commands(Environment::inMemory(
            [], // no chunks in STDIN
            false, // non interactive mode
            ['cli.php', 'greet', 'Bob'], // to simulate `php cli.php greet Bob`
            [], // environment variables
            '/tmp/', // working directory path
        ))->unwrap();

        // it asked to output "Hi Bob\n"
        $this->assertSame(
            ["Hi Bob\n"],
            $environment
                ->outputted()
                ->filter(static fn($chunk) => $chunk[1] === 'output')
                ->map(static fn($chunk) => $chunk[0]->toString())
                ->toList(),
        );
        // it didn't write anything to STDERR
        $this->assertSame(
            [],
            $environment
                ->outputted()
                ->filter(static fn($chunk) => $chunk[1] === 'error')
                ->toList(),
        );
        // it didn't specify any exit code, meaning it will default to 0
        $this->assertFalse($environment->exitCode()->match(
            static fn() => true,
            static fn() => false,
        ));
    }
}
```
