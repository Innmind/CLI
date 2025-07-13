<?php
declare(strict_types = 1);

namespace Innmind\CLI\Command\Pattern;

use Innmind\CLI\Command\Usage;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 * @internal
 */
interface Input
{
    /**
     * @return Maybe<Usage>
     */
    public static function walk(Usage $usage, Str $pattern): Maybe;
}
