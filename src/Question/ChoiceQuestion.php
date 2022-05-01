<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\Immutable\{
    Str,
    Map,
    Set,
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
     * @throws NonInteractiveTerminal
     *
     * @return array{Map<scalar, scalar>, Environment}
     */
    public function __invoke(Environment $env): array
    {
        if (!$env->interactive() || $env->arguments()->contains('--no-interaction')) {
            throw new NonInteractiveTerminal;
        }

        $env = $env->output($this->question->append("\n"));
        $env = $this->values->reduce(
            $env,
            static function(Environment $env, $key, $value): Environment {
                return $env->output(Str::of("[%s] %s\n")->sprintf((string) $key, (string) $value));
            },
        );
        $env = $env->output(Str::of('> '));

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

        return [
            $this->values->filter(static function($key) use ($choices): bool {
                return $choices->contains((string) $key);
            }),
            $env,
        ];
    }
}
