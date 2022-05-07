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
        $env = $response->match(
            static fn($response) => $env->output($response->append("\n")),
            static fn() => $env->output(Str::of("No response\n")),
        );

        [$response, $env] = $pwd($env);
        $env = $response->match(
            static fn($response) => $env->output($response->append("\n")),
            static fn() => $env->output(Str::of("No response\n")),
        );

        $ask = new ChoiceQuestion(
            'choices:',
            Map::of()
                ('foo', 'bar')
                (1, 'baz')
                (2, 3)
                (3, 'foo')
        );

        [$choices, $env] = $ask($env);
        $choices = $choices->match(
            static fn($choices) => $choices,
            static fn() => Map::of(),
        );

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
