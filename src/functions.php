<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\Filesystem\Adapter;
use Innmind\Immutable\{
    MapInterface,
    Pair,
    Str,
};
use Symfony\Component\Dotenv\Dotenv;

/**
 * @param $variables MapInterface<string, string>
 *
 * @return MapInterface<string, string>
 */
function variables(MapInterface $variables, Adapter $config): MapInterface
{
    if ($config->has('.env')) {
        $dot = (new Dotenv)->parse((string) $config->get('.env')->content());

        foreach ($dot as $key => $value) {
            $variables = $variables->put($key, $value);
        }
    }

    return $variables->map(static function(string $name, $value): Pair {
        return new Pair(
            (string) Str::of($name)->toLower()->camelize()->lcfirst(),
            $value
        );
    });
}
