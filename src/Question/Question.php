<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\CLI\Environment;
use Innmind\Stream\{
    Readable,
    Writable,
    Watch\Select,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\Str;

final class Question
{
    private Str $question;
    private bool $hiddenResponse = false;

    public function __construct(string $question)
    {
        $this->question = Str::of($question)->append(' ');
    }

    public static function hiddenResponse(string $question): self
    {
        $self = new self($question);
        $self->hiddenResponse = true;

        return $self;
    }

    public function __invoke(Environment $env): Str
    {
        $input = $env->input();
        $output = $env->output();
        $output->write($this->question);

        /** @psalm-suppress InvalidArgument $input must be a Selectable */
        $select = (new Select(new ElapsedPeriod(60 * 1000))) // one minute
            ->forRead($input);

        $response = Str::of('');

        if ($this->hiddenResponse) {
            /**
             * @psalm-suppress ForbiddenCode
             * @var string
             */
            $sttyMode = \shell_exec('stty -g');
            /** @psalm-suppress ForbiddenCode */
            \shell_exec('stty -echo'); // disable character print
        }

        try {
            do {
                $ready = $select();

                /** @psalm-suppress InvalidArgument $input must be a Selectable */
                if ($ready->toRead()->contains($input)) {
                    $response = $response->append($input->read()->toString());
                }
            } while (!$response->contains("\n"));
        } finally {
            if ($this->hiddenResponse) {
                /**
                 * @psalm-suppress PossiblyUndefinedVariable
                 * @psalm-suppress ForbiddenCode
                 */
                \shell_exec('stty '.$sttyMode);
                $output->write(Str::of("\n")); // to display the new line
            }
        }

        return $response->substring(0, -1); // remove the new line character
    }
}
