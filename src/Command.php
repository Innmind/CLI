<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\Command\{
    Arguments,
};

interface Command
{
    public function __invoke(Environment $env, Arguments $arguments): void;
    public function __toString(): string;
}
