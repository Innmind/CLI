<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Console,
};
use Innmind\Immutable\{
    Str,
    Attempt
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
     * @return array{Attempt<Str>, T} Returns nothing when no interactions available
     */
    public function __invoke(Environment|Console $env): array
    {
        $noInteraction = match ($env::class) {
            Console::class => $env->options()->contains('no-interaction'),
            default => $env->arguments()->contains('--no-interaction'),
        };

        if (!$env->interactive() || $noInteraction) {
            /** @var array{Attempt<Str>, T} */
            return [
                Attempt::error(new \RuntimeException('Not in an interactive mode')),
                $env,
            ];
        }

        $env = $env->output($this->question)->unwrap();

        $response = Str::of('');

        do {
            [$input, $env] = $env->read();

            $response = $input->match(
                static fn($input) => $response->append($input->toString()),
                static fn() => $response,
            );
        } while (!$response->contains("\n"));

        /** @var array{Attempt<Str>, T} */
        return [Attempt::result($response->dropEnd(1)), $env]; // remove the new line character
    }
}
