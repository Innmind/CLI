<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
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
        $response = $response->match(
            static fn($response) => $response,
            static fn() => null,
        );

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
        $this->assertSame(
            ['message '],
            $env->outputs(),
        );
    }

    public function testReturnNothingWhenEnvNonInteractive()
    {
        $question = new Question('watev');

        $env = Environment\InMemory::of(
            [],
            false,
            [],
            [],
            '/',
        );

        [$response, $env] = $question($env);

        $this->assertNull($response->match(
            static fn($response) => $response,
            static fn() => null,
        ));
    }

    public function testReturnNothingWhenOptionToSpecifyNoInteractionIsRequired()
    {
        $question = new Question('watev');

        $env = Environment\InMemory::of(
            [],
            true,
            ['foo', '--no-interaction', 'bar'],
            [],
            '/',
        );

        [$response, $env] = $question($env);

        $this->assertNull($response->match(
            static fn($response) => $response,
            static fn() => null,
        ));
    }
}
