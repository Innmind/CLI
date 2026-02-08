#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Attempt;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): Attempt
    {
        throw new \LogicException('waaat');
    }
};
