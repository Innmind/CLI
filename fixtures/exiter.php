<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment
};

new class extends Main {
    protected function main(Environment $env): void
    {
        $env->exit((int) $env->arguments()->last());
    }
};
