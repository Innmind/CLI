<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\ChoiceQuestion,
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ChoiceQuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new ChoiceQuestion(
            'message',
            Map::of()
                ('foo', 'bar')
                (1, 'baz')
                (2, 3)
                ('bar', 3),
        );
        $env = Environment\InMemory::of(
            [' foo,  ', "2\n"],
            true,
            [],
            [],
            '/',
        );

        [$response, $env] = $question($env);

        $this->assertInstanceOf(Map::class, $response);
        $this->assertCount(2, $response);
        $this->assertSame('bar', $response->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(3, $response->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [
                "message\n",
                "[foo] bar\n",
                "[1] baz\n",
                "[2] 3\n",
                "[bar] 3\n",
                '> ',
            ],
            $env->outputs(),
        );
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new ChoiceQuestion('watev', Map::of());

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
        $question = new ChoiceQuestion('watev', Map::of());

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
