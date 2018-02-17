<?php
declare(strict_types = 1);

namespace Innmind\CLI;

abstract class Main
{
    final public function __construct()
    {
        $this->main($env = new Environment\GlobalEnvironment);
        exit($env->exitCode()->toInt());
    }

    abstract protected function main(Environment $env): void;

    final public function __destruct()
    {
        //main() is the only place to run code
    }
}
