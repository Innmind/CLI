<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\{
    Environment,
    Exception\NonInteractiveTerminal,
};
use Innmind\OperatingSystem\Sockets;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\Str;

final class Question
{
    private Str $question;

    public function __construct(string $question)
    {
        $this->question = Str::of($question)->append(' ');
    }

    /**
     * @throws NonInteractiveTerminal
     */
    public function __invoke(Environment $env, Sockets $sockets): Str
    {
        if (!$env->interactive() || $env->arguments()->contains('--no-interaction')) {
            throw new NonInteractiveTerminal;
        }

        $input = $env->input();
        $output = $env->output();
        $output->write($this->question);

        /** @psalm-suppress InvalidArgument $input must be a Selectable */
        $watch = $sockets->watch(new ElapsedPeriod(60 * 1000)) // one minute
            ->forRead($input);

        $response = Str::of('');

        do {
            $ready = $watch();

            /** @psalm-suppress InvalidArgument $input must be a Selectable */
            if ($ready->toRead()->contains($input)) {
                $response = $response->append($input->read()->toString());
            }
        } while (!$response->contains("\n"));

        return $response->substring(0, -1); // remove the new line character
    }
}
