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
    private function __construct(private Str $question)
    {
    }

    /**
     * @template T of Environment|Console
     *
     * @param T $env
     *
     * @return Attempt<array{Attempt<Str>, T}> Returns nothing when no interactions available
     */
    #[\NoDiscard]
    public function __invoke(Environment|Console $env): Attempt
    {
        $noInteraction = match ($env::class) {
            Console::class => $env->options()->contains('no-interaction'),
            default => $env->arguments()->contains('--no-interaction'),
        };

        if (!$env->interactive() || $noInteraction) {
            /** @var Attempt<array{Attempt<Str>, T}> */
            return Attempt::result([
                Attempt::error(new \RuntimeException('Not in an interactive mode')),
                $env,
            ]);
        }

        /** @var Attempt<array{Attempt<Str>, T}> */
        return $env
            ->output($this->question)
            ->map($this->read(...));
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $question): self
    {
        return new self(Str::of($question)->append(' '));
    }

    /**
     * @template I of Environment|Console
     *
     * @param I $env
     *
     * @return array{Attempt<Str>, I}
     */
    private function read(Environment|Console $env): array
    {
        $response = Str::of('');

        do {
            [$input, $env] = $env->read();

            $response = $input->match(
                static fn($input) => $response->append($input->toString()),
                static fn() => $response,
            );
        } while (!$response->contains("\n"));

        /** @var array{Attempt<Str>, I} */
        return [Attempt::result($response->dropEnd(1)), $env]; // remove the new line character
    }
}
