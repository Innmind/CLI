<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
    Question\Question,
    Question\ChoiceQuestion,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Map,
    Str,
};

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $user = new Question('your name please :');
        $pwd = Question::hiddenResponse('password :');

        $env->output()->write($user($env)->append("\n"));
        $env->output()->write($pwd($env)->append("\n"));

        $ask = new ChoiceQuestion(
            'choices:',
            Map::of('scalar', 'scalar')
                ('foo', 'bar')
                (1, 'baz')
                (2, 3)
                (3, 'foo')
        );

        $choices = $ask($env);

        $choices->foreach(static function($key, $value) use ($env): void {
            $env->output()->write(
                Str::of("%s(%s) => %s(%s)\n")->sprintf(
                    (string) gettype($key),
                    (string) $key,
                    gettype($value),
                    (string) $value,
                ),
            );
        });
    }
};
