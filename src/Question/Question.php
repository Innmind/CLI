<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Console,
};
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
     * @template T of Environment|Console
     *
     * @param T $env
     *
     * @return array{Maybe<Str>, T} Returns nothing when no interactions available
     */
    public function __invoke(Environment|Console $env): array
    {
        $noInteraction = match ($env::class) {
            Console::class => $env->options()->contains('no-interaction'),
            default => $env->arguments()->contains('--no-interaction'),
        };

        if (!$env->interactive() || $noInteraction) {
            /** @var array{Maybe<Str>, T} */
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

        /** @var array{Maybe<Str>, T} */
        return [Maybe::just($response->dropEnd(1)), $env]; // remove the new line character
    }
}
