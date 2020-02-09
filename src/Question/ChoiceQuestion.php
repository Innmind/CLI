<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\Stream\{
    Readable,
    Writable,
    Watch\Select,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    Map,
    Set,
};

final class ChoiceQuestion
{
    private Str $question;
    private Map $values;

    /**
     * @param Map<scalar, scalar> $values
     */
    public function __construct(string $question, Map $values)
    {
        if (
            (string) $values->keyType() !== 'scalar' ||
            (string) $values->valueType() !== 'scalar'
        ) {
            throw new \TypeError('Argument 2 must be of type Map<scalar, scalar>');
        }

        $this->question = Str::of($question);
        $this->values = $values;
    }

    /**
     * @return Map<scalar, scalar>
     */
    public function __invoke(Readable $input, Writable $output): Map
    {
        $output->write($this->question->append("\n"));
        $this->values->foreach(static function($key, $value) use ($output): void {
            $output->write(Str::of("[%s] %s\n")->sprintf((string) $key, (string) $value));
        });
        $output->write(Str::of('> '));

        $select = (new Select(new ElapsedPeriod(60 * 1000))) // one minute
            ->forRead($input);

        $response = Str::of('');

        do {
            $ready = $select();

            if ($ready->toRead()->contains($input)) {
                $response = $response->append($input->read()->toString());
            }
        } while (!$response->contains("\n"));

        $choices = $response
            ->substring(0, -1) // remove the new line character
            ->split(',')
            ->reduce(
                Set::of('string'),
                static function(Set $choices, Str $choice): Set {
                    return $choices->add($choice->trim()->toString());
                }
            );

        return $this->values->filter(static function($key) use ($choices): bool {
            return $choices->contains((string) $key);
        });
    }
}
