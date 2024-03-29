<?php
declare(strict_types = 1);

namespace Innmind\CLI;

use Innmind\CLI\{
    Command\Arguments,
    Command\Options,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Maybe,
    Map,
    Str,
};

/**
 * @psalm-immutable
 */
final class Console
{
    private Arguments $arguments;
    private Options $options;
    private Environment $env;

    private function __construct(
        Arguments $arguments,
        Options $options,
        Environment $env,
    ) {
        $this->arguments = $arguments;
        $this->options = $options;
        $this->env = $env;
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
     * @return array{Maybe<Str>, self}
     */
    public function read(int $length = null): array
    {
        [$data, $env] = $this->env->read($length);

        return [$data, new self(
            $this->arguments,
            $this->options,
            $env,
        )];
    }

    public function output(Str $data): self
    {
        return new self(
            $this->arguments,
            $this->options,
            $this->env->output($data),
        );
    }

    public function error(Str $data): self
    {
        return new self(
            $this->arguments,
            $this->options,
            $this->env->error($data),
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
