<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Question
{
    private Str $question;

    public function __construct(string $question)
    {
        $this->question = Str::of($question)->append(' ');
    }

    /**
     * @throws NonInteractiveTerminal
     *
     * @return array{Str, Environment}
     */
    public function __invoke(Environment $env): array
    {
        if (!$env->interactive() || $env->arguments()->contains('--no-interaction')) {
            throw new NonInteractiveTerminal;
        }

        $env = $env->output($this->question);

        $response = Str::of('');

        do {
            [$input, $env] = $env->read();

            $response = $input->match(
                static fn($input) => $response->append($input->toString()),
                static fn() => $response,
            );
        } while (!$response->contains("\n"));

        return [$response->dropEnd(1), $env]; // remove the new line character
    }
}
