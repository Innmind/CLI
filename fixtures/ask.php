<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Question\Question,
};

new class extends Main {
    protected function main(Environment $env): void
    {
        $user = new Question('your name please :');
        $pwd = Question::hiddenResponse('password :');

        $env
            ->output()
            ->write($user($env->input(), $env->output())->append("\n"))
            ->write($pwd($env->input(), $env->output())->append("\n"));
    }
};
