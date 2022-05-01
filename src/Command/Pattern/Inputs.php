<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Inputs
{
    /** list<class-string<Input>> */
    private array $inputs;

    public function __construct()
    {
        $this->inputs = [
            RequiredArgument::class,
            OptionalArgument::class,
            PackArgument::class,
            OptionFlag::class,
            OptionWithValue::class,
        ];
    }

    public function __invoke(Str $pattern): Input
    {
        /** @var class-string<Input> $input */
        foreach ($this->inputs as $input) {
            try {
                /** @var Input */
                return [$input, 'of']($pattern);
            } catch (PatternNotRecognized $e) {
                //pass
            }
        }

        throw new PatternNotRecognized($pattern->toString());
    }
}
