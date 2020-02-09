<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\Stream\{
    Readable,
    Writable,
    Select,
};
use Innmind\TimeContinuum\ElapsedPeriod;
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

    public function __invoke(Readable $input, Writable $output): Str
    {
        $output->write($this->question);

        $select = (new Select(new ElapsedPeriod(60 * 1000))) // one minute
            ->forRead($input);

        $response = Str::of('');

        if ($this->hiddenResponse) {
            $sttyMode = shell_exec('stty -g');
            shell_exec('stty -echo'); // disable character print
        }

        try {
            do {
                $streams = $select();

                if ($streams->get('read')->contains($input)) {
                    $response = $response->append((string) $input->read());
                }
            } while (!$response->contains("\n"));
        } finally {
            if ($this->hiddenResponse) {
                shell_exec('stty '.$sttyMode);
                $output->write(Str::of("\n")); // to display the new line
            }
        }

        return $response->substring(0, -1); // remove the new line character
    }
}
