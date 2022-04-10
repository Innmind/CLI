<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\OperatingSystem\Sockets;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    Map,
    Set,
};

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
     * @return Map<scalar, scalar>
     */
    public function __invoke(Environment $env, Sockets $sockets): Map
    {
        if (!$env->interactive() || $env->arguments()->contains('--no-interaction')) {
            throw new NonInteractiveTerminal;
        }

        $input = $env->input();
        $output = $env->output();
        $output->write($this->question->append("\n"));
        $_ = $this->values->foreach(static function($key, $value) use ($output): void {
            $output->write(Str::of("[%s] %s\n")->sprintf((string) $key, (string) $value));
        });
        $output->write(Str::of('> '));

        /** @psalm-suppress InvalidArgument $input must be a Selectable */
        $select = $sockets->watch(new ElapsedPeriod(60 * 1000)) // one minute
            ->forRead($input);

        $response = Str::of('');

        do {
            /** @psalm-suppress InvalidArgument */
            $response = $select()
                ->map(static fn($ready) => $ready->toRead())
                ->filter(static fn($toRead) => $toRead->contains($input))
                ->flatMap(static fn() => $input->read())
                ->match(
                    static fn($input) => $response->append($input->toString()),
                    static fn() => $response,
                );
        } while (!$response->contains("\n"));

        $choices = $response
            ->dropEnd(1) // remove the new line character
            ->split(',')
            ->map(static fn($choice) => $choice->trim()->toString());

        return $this->values->filter(static function($key) use ($choices): bool {
            return $choices->contains((string) $key);
        });
    }
}
