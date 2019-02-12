<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI;

use function Innmind\CLI\variables;
use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    File\File,
    Stream\StringStream,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testVariables()
    {
        $variables = variables(
            Map::of('string', 'string')
                ('FOO_BAR', '42'),
            new MemoryAdapter
        );

        $this->assertTrue(
            $variables->equals(
                Map::of('string', 'string')
                    ('fooBar', '42')
            )
        );
    }

    public function testVariablesWithDotEnvFile()
    {
        $adapter = new MemoryAdapter;
        $adapter->add(new File(
            '.env',
            new StringStream("BAZ=fOo")
        ));

        $variables = variables(
            Map::of('string', 'string')
                ('FOO_BAR', '42'),
            $adapter
        );

        $this->assertTrue(
            $variables->equals(
                Map::of('string', 'string')
                    ('fooBar', '42')
                    ('baz', 'fOo')
            )
        );
    }
}
