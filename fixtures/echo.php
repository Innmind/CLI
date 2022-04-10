<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Str;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $env->output()->write(
            $env->input()->read()->match(
                static fn($str) => $str,
                static fn() => Str::of(''),
            ),
        );
    }
};
