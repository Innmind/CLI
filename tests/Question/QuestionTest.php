<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new Question('message');
        $env = Environment\InMemory::of(
            ['f', "oo\n"],
            true,
            [],
            [],
            '/',
        );

        [$response, $env] = $question($env);

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
        $this->assertSame(
            ['message '],
            $env->outputs(),
        );
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new Question('watev');

        $env = Environment\InMemory::of(
            [],
            false,
            [],
            [],
            '/',
        );

        $this->expectException(NonInteractiveTerminal::class);

        $question($env);
    }

    public function testThrowWhenOptionToSpecifyNoInteractionIsRequired()
    {
        $question = new Question('watev');

        $env = Environment\InMemory::of(
            [],
            true,
            ['foo', '--no-interaction', 'bar'],
            [],
            '/',
        );

        $this->expectException(NonInteractiveTerminal::class);

        $question($env);
    }
}
