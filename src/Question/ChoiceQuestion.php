<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Console,
};
use Innmind\Immutable\{
    Str,
    Map,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class ChoiceQuestion
{
    private Str $question;
    /** @var Map<scalar, scalar> */
    private Map $values;

    /**
     * @param Map<scalar, scalar> $values
     */
    public function __construct(string $question, Map $values)
    {
        $this->question = Str::of($question);
        $this->values = $values;
    }

    /**
     * @template T of Environment|Console
     *
     * @param T $env
     *
     * @return array{Maybe<Map<scalar, scalar>>, T} Returns nothing when no interactions available
     */
    public function __invoke(Environment|Console $env): array
    {
        $noInteraction = match ($env::class) {
            Console::class => $env->options()->contains('no-interaction'),
            default => $env->arguments()->contains('--no-interaction'),
        };

        if (!$env->interactive() || $noInteraction) {
            /** @var array{Maybe<Map<scalar, scalar>>, T} */
            return [Maybe::nothing(), $env];
        }

        $env = $env->output($this->question->append("\n"))->unwrap();
        $env = $this
            ->values
            ->toSequence()
            ->sink($env)
            ->attempt(static fn($env, $pair) => $env->output(
                Str::of("[%s] %s\n")->sprintf(
                    (string) $pair->key(),
                    (string) $pair->value(),
                ),
            ))
            ->unwrap();
        $env = $env->output(Str::of('> '))->unwrap();

        $response = Str::of('');

        do {
            [$input, $env] = $env->read();

            $response = $input->match(
                static fn($input) => $response->append($input->toString()),
                static fn() => $response,
            );
        } while (!$response->contains("\n"));

        $choices = $response
            ->dropEnd(1) // remove the new line character
            ->split(',')
            ->map(static fn($choice) => $choice->trim()->toString());

        /** @var array{Maybe<Map<scalar, scalar>>, T} */
        return [
            Maybe::just($this->values->filter(static function($key) use ($choices): bool {
                return $choices->contains((string) $key);
            })),
            $env,
        ];
    }
}
