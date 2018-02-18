<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Command;

use Innmind\CLI\{
    Command\Specification,
    Command\Arguments,
    Command\Options,
    Command\Pattern,
    Command,
    Environment,
    Exception\EmptyDeclaration,
};
use PHPUnit\Framework\TestCase;

class SpecificationTest extends TestCase
{
    public function testInterface()
    {
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function __toString(): string
            {
                return <<<USAGE
    watch container [output] ...proxy

    Watch a container definition file for changes and generate corresponding graph

    The output argument is optional, when ommitted it will print the graphviz dot
    content but if provided it will automatically generate the graph to the given file.

    The proxy pack argument are arguments that will be sent used for the graphviz command.
USAGE;
            }
        };

        $spec = new Specification($command);

        $this->assertSame('watch', $spec->name());
        $this->assertSame(
            'Watch a container definition file for changes and generate corresponding graph',
            $spec->shortDescription()
        );

        $expected = <<<DESCRIPTION
The output argument is optional, when ommitted it will print the graphviz dot
content but if provided it will automatically generate the graph to the given file.

The proxy pack argument are arguments that will be sent used for the graphviz command.
DESCRIPTION;

        $this->assertSame($expected, $spec->description());
        $this->assertSame(
            'watch container [output] ...proxy',
            (string) $spec
        );
        $this->assertInstanceOf(Pattern::class, $spec->pattern());
        $this->assertSame('container [output] ...proxy', (string) $spec->pattern());
    }

    public function testThrowWhenEmptyDeclaration()
    {
        $command = new class implements Command {
            public function __invoke(Environment $env, Arguments $arguments, Options $options): void
            {
            }

            public function __toString(): string
            {
                return '  ';
            }
        };

        $this->expectException(EmptyDeclaration::class);

        new Specification($command);
    }
}
