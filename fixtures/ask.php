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
    protected function main(Environment $env, OperatingSystem $os): Environment
    {
        $user = new Question('your name please :');
        $pwd = new Question('password :');

        [$response, $env] = $user($env);
        $env->output($response->append("\n"));

        [$response, $env] = $pwd($env);
        $env->output($response->append("\n"));

        $ask = new ChoiceQuestion(
            'choices:',
            Map::of()
                ('foo', 'bar')
                (1, 'baz')
                (2, 3)
                (3, 'foo')
        );

        [$choices, $env] = $ask($env);

        return $choices->reduce(
            $env,
            static function($env, $key, $value): Environment {
                return $env->output(
                    Str::of("%s(%s) => %s(%s)\n")->sprintf(
                        (string) gettype($key),
                        (string) $key,
                        gettype($value),
                        (string) $value,
                    ),
                );
            },
        );

        return $env;
    }
};
