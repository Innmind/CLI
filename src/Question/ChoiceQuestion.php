<?php
declare(strict_types = 1);

namespace Innmind\CLI\Question;

use Innmind\Stream\{
    Readable,
    Writable,
    Select,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    MapInterface,
    Set,
};

final class ChoiceQuestion
{
    private $question;
    private $values;

    /**
     * @param MapInterface<scalar, scalar> $values
     */
    public function __construct(string $question, MapInterface $values)
    {
        if (
            (string) $values->keyType() !== 'scalar' ||
            (string) $values->valueType() !== 'scalar'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<scalar, scalar>');
        }

        $this->question = Str::of($question);
        $this->values = $values;
    }

    /**
     * @return MapInterface<scalar, scalar>
     */
    public function __invoke(Readable $input, Writable $output): MapInterface
    {
        $output->write($this->question->append("\n"));
        $this->values->foreach(static function($key, $value) use ($output): void {
            $output->write(Str::of("[%s] %s\n")->sprintf($key, $value));
        });
        $output->write(Str::of('> '));

        $select = (new Select(new ElapsedPeriod(60 * 1000))) // one minute
            ->forRead($input);

        $response = Str::of('');

        do {
            $streams = $select();

            if ($streams->get('read')->contains($input)) {
                $response = $response->append((string) $input->read());
            }
        } while (!$response->contains("\n"));

        $choices = $response
            ->substring(0, -1) // remove the new line character
            ->split(',')
            ->reduce(
                Set::of('string'),
                static function(Set $choices, Str $choice): Set {
                    return $choices->add((string) $choice->trim());
                }
            );

        return $this->values->filter(static function($key) use ($choices): bool {
            return $choices->contains((string) $key);
        });
    }
}
