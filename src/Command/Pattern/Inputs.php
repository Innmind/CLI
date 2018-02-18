<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Exception\PatternNotRecognized;
use Innmind\Immutable\Str;

final class Inputs
{
    private $inputs;

    public function __construct()
    {
        $this->inputs = [
            RequiredArgument::class,
            OptionalArgument::class,
            PackArgument::class,
        ];
    }

    public function load(Str $pattern): Input
    {
        foreach ($this->inputs as $input) {
            try {
                return [$input, 'fromString']($pattern);
            } catch (PatternNotRecognized $e) {
                //pass
            }
        }

        throw new PatternNotRecognized((string) $pattern);
    }
}
