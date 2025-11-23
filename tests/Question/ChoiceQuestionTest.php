<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\ChoiceQuestion,
    Environment,
};
use Innmind\Immutable\Map;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ChoiceQuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = ChoiceQuestion::of(
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

        [$response, $env] = $question($env)->unwrap();
        $response = $response->match(
            static fn($response) => $response,
            static fn() => null,
        );

        $this->assertInstanceOf(Map::class, $response);
        $this->assertSame(2, $response->size());
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

    public function testReturnNothingWhenEnvNonInteractive()
    {
        $question = ChoiceQuestion::of('watev', Map::of());

        $env = Environment\InMemory::of(
            [],
            false,
            [],
            [],
            '/',
        );

        [$response, $env] = $question($env)->unwrap();

        $this->assertNull($response->match(
            static fn($response) => $response,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenOptionToSpecifyNoInteractionIsRequired()
    {
        $question = ChoiceQuestion::of('watev', Map::of());

        $env = Environment\InMemory::of(
            [],
            true,
            ['foo', '--no-interaction', 'bar'],
            [],
            '/',
        );

        [$response, $env] = $question($env)->unwrap();

        $this->assertNull($response->match(
            static fn($response) => $response,
            static fn() => null,
        ));
    }
}
