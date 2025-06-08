<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Str,
    Attempt,
};

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        [$input, $env] = $env->read();

        return $env->output(
            $input->match(
                static fn($str) => $str,
                static fn() => Str::of(''),
            ),
        );
    }
};
