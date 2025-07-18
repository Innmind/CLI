<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Arguments,
    Command\Options,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Map,
    Str,
};

/**
 * @psalm-immutable
 */
final class Console
{
    private function __construct(
        private Arguments $arguments,
        private Options $options,
        private Environment $env,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Environment $env,
        Arguments $arguments,
        Options $options,
    ): self {
        return new self(
            $arguments,
            $options,
            $env,
        );
    }

    public function arguments(): Arguments
    {
        return $this->arguments;
    }

    public function options(): Options
    {
        return $this->options;
    }

    /**
     * @param positive-int|null $length
     *
     * @return array{Attempt<Str>, self}
     */
    public function read(?int $length = null): array
    {
        [$data, $env] = $this->env->read($length);

        return [$data, new self(
            $this->arguments,
            $this->options,
            $env,
        )];
    }

    /**
     * @return Attempt<self>
     */
    public function output(Str $data): Attempt
    {
        return $this->env->output($data)->map(
            fn($output) => new self(
                $this->arguments,
                $this->options,
                $output,
            ),
        );
    }

    /**
     * @return Attempt<self>
     */
    public function error(Str $data): Attempt
    {
        return $this->env->error($data)->map(
            fn($output) => new self(
                $this->arguments,
                $this->options,
                $output,
            ),
        );
    }

    public function interactive(): bool
    {
        return $this->env->interactive();
    }

    /**
     * @return Map<string, string>
     */
    public function variables(): Map
    {
        return $this->env->variables();
    }

    public function workingDirectory(): Path
    {
        return $this->env->workingDirectory();
    }

    /**
     * @param int<0, 254> $exit
     */
    public function exit(int $exit): self
    {
        return new self(
            $this->arguments,
            $this->options,
            $this->env->exit($exit),
        );
    }

    /**
     * @internal
     */
    public function environment(): Environment
    {
        return $this->env;
    }
}
