#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Exception\LogicException,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Str;

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        throw new LogicException('waaat');
    }
};
