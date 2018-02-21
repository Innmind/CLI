#!/usr/bin/env php
<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Exception\LogicException,
};
use Innmind\Immutable\Str;

new class extends Main {
    protected function main(Environment $env): void
    {
        throw new LogicException('waaat');
    }
};
