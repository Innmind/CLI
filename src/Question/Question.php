<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\Environment;
use Innmind\Immutable\{
    Str,
    Maybe,
};

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
     * @return array{Maybe<Str>, Environment} Returns nothing when no interactions available
     */
    public function __invoke(Environment $env): array
    {
        if (!$env->interactive() || $env->arguments()->contains('--no-interaction')) {
            /** @var array{Maybe<Str>, Environment} */
            return [Maybe::nothing(), $env];
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

        return [Maybe::just($response->dropEnd(1)), $env]; // remove the new line character
    }
}
