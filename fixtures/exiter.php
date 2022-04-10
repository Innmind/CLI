<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment
};
use Innmind\OperatingSystem\OperatingSystem;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $env->exit((int) $env->arguments()->last()->match(
            static fn($last) => $last,
            static fn() => null,
        ));
    }
};
