<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Question;

use Innmind\CLI\{
    Question\Question,
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInvoke()
    {
        $question = new Question('message');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls(
                [Maybe::just(Str::of('f')), $env],
                [Maybe::just(Str::of("oo\n")), $env],
            ));
        $env
            ->expects($this->any())
            ->method('output')
            ->with(Str::of('message '))
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

        $this->assertInstanceOf(Str::class, $response);
        $this->assertSame('foo', $response->toString());
    }

    public function testThrowWhenEnvNonInteractive()
    {
        $question = new Question('watev');

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
        $question = new Question('watev');

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
