<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\ChoiceQuestion,
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\{
    Str,
    Map,
    Sequence,
    Maybe,
};
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
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls(
                [Maybe::just(Str::of(' foo,  ')), $env],
                [Maybe::just(Str::of("2\n")), $env],
            ));
        $env
            ->expects($this->exactly(6))
            ->method('output')
            ->withConsecutive(
                [Str::of("message\n")],
                [Str::of("[foo] bar\n")],
                [Str::of("[1] baz\n")],
                [Str::of("[2] 3\n")],
                [Str::of("[bar] 3\n")],
                [Str::of('> ')],
            )
            ->will($this->returnSelf());
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(true);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::strings());

        [$response] = $question($env);

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
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new ChoiceQuestion('watev', Map::of());

        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(false);

        $this->expectException(NonInteractiveTerminal::class);

        $question($env);
    }

    public function testThrowWhenOptionToSpecifyNoInteractionIsRequired()
    {
        $question = new ChoiceQuestion('watev', Map::of());

        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('interactive')
            ->willReturn(true);
        $env
            ->expects($this->once())
            ->method('arguments')
            ->willReturn(Sequence::strings('foo', '--no-interaction', 'bar'));

        $this->expectException(NonInteractiveTerminal::class);

        $question($env);
    }
}
