<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\Filesystem\{
    Adapter,
    Name,
};
use Innmind\Immutable\{
    Map,
    Pair,
    Str,
};
use Symfony\Component\Dotenv\Dotenv;

/**
 * @param Map<string, string> $variables
 *
 * @return Map<string, string>
 */
function variables(Map $variables, Adapter $config): Map
{
    if ($config->contains(new Name('.env'))) {
        /** @var array<string, string> */
        $dot = (new Dotenv)->parse($config->get(new Name('.env'))->content()->toString());

        foreach ($dot as $key => $value) {
            $variables = $variables->put($key, $value);
        }
    }

    return $variables->map(static function(string $name, $value): Pair {
        return new Pair(
            Str::of($name)->toLower()->camelize()->toString(),
            $value,
        );
    });
}
