<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Command\{
    Arguments,
    Options,
};

interface Command
{
    public function __invoke(Environment $env, Arguments $arguments, Options $options): void;
    public function toString(): string;
}
