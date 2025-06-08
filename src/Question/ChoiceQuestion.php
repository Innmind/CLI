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
    Attempt,
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
     * @return Attempt<array{Attempt<Map<scalar, scalar>>, T}> Returns nothing when no interactions available
     */
    public function __invoke(Environment|Console $env): Attempt
    {
        $noInteraction = match ($env::class) {
            Console::class => $env->options()->contains('no-interaction'),
            default => $env->arguments()->contains('--no-interaction'),
        };

        if (!$env->interactive() || $noInteraction) {
            /** @var Attempt<array{Attempt<Map<scalar, scalar>>, T}> */
            return Attempt::result([
                Attempt::error(new \RuntimeException('Not in an interactive mode')),
                $env,
            ]);
        }

        /** @var Attempt<array{Attempt<Map<scalar, scalar>>, T}> */
        return $env
            ->output($this->question->append("\n"))
            ->flatMap(
                fn($env) => $this
                    ->values
                    ->toSequence()
                    ->sink($env)
                    ->attempt(static fn($env, $pair) => $env->output(
                        Str::of("[%s] %s\n")->sprintf(
                            (string) $pair->key(),
                            (string) $pair->value(),
                        ),
                    )),
            )
            ->flatMap(static fn($env) => $env->output(Str::of('> ')))
            ->map($this->read(...));
    }

    /**
     * @template I of Environment|Console
     *
     * @param I $env
     *
     * @return array{Attempt<Map<scalar, scalar>>, I}
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

        $choices = $response
            ->dropEnd(1) // remove the new line character
            ->split(',')
            ->map(static fn($choice) => $choice->trim()->toString());

        /** @var array{Attempt<Map<scalar, scalar>>, I} */
        return [
            Attempt::result($this->values->filter(static function($key) use ($choices): bool {
                return $choices->contains((string) $key);
            })),
            $env,
        ];
    }
}
