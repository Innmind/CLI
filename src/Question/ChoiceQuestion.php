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
use Innmind\Immutable\{
    Str,
    Map,
    Set,
};
use function Innmind\Immutable\assertMap;

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
        assertMap('scalar', 'scalar', $values, 2);

        $this->question = Str::of($question);
        $this->values = $values;
    }

    /**
     * @return Map<scalar, scalar>
     */
    public function __invoke(Environment $env): Map
    {
        $input = $env->input();
        $output = $env->output();
        $output->write($this->question->append("\n"));
        $this->values->foreach(static function($key, $value) use ($output): void {
            $output->write(Str::of("[%s] %s\n")->sprintf((string) $key, (string) $value));
        });
        $output->write(Str::of('> '));

        /** @psalm-suppress InvalidArgument $input must be a Selectable */
        $select = (new Select(new ElapsedPeriod(60 * 1000))) // one minute
            ->forRead($input);

        $response = Str::of('');

        do {
            $ready = $select();

            /** @psalm-suppress InvalidArgument $input must be a Selectable */
            if ($ready->toRead()->contains($input)) {
                $response = $response->append($input->read()->toString());
            }
        } while (!$response->contains("\n"));

        $choices = $response
            ->substring(0, -1) // remove the new line character
            ->split(',')
            ->toSetOf(
                'string',
                static fn(Str $choice): \Generator => yield $choice->trim()->toString(),
            );

        return $this->values->filter(static function($key) use ($choices): bool {
            return $choices->contains((string) $key);
        });
    }
}
