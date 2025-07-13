<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = Question::of('message');
        $env = Environment\InMemory::of(
            ['f', "oo\n"],
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

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
        $this->assertSame(
            ['message '],
            $env->outputs(),
        );
    }

    public function testReturnNothingWhenEnvNonInteractive()
    {
        $question = Question::of('watev');

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
        $question = Question::of('watev');

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
