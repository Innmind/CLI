# CLI

[![CI](https://github.com/Innmind/CLI/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/Innmind/CLI/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Innmind/CLI/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/CLI)
[![Type Coverage](https://shepherd.dev/github/Innmind/CLI/coverage.svg)](https://shepherd.dev/github/Innmind/CLI)

CLI is a small library to wrap all the needed informations to build a command line tool. The idea to build this came while reading the [ponylang](https://www.ponylang.org/) [documentation](https://tutorial.ponylang.org/getting-started/how-it-works.html) realising that other languages use a similar approach for the entry point of the app, so I decided to have something similar for PHP.

The said approach is to have a `main` function as the starting point of execution of your code. This function has a the environment it runs in passed as argument so there's no need for global variables. However since not everything can be passed down as argument (it would complicate the interface), [ambient authority](https://en.wikipedia.org/wiki/Ambient_authority) can be exercised (as in regular PHP script).

> [!IMPORTANT]
> To correctly use this library you must validate your code with [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm)

## Installation

```sh
composer require innmind/cli
```

## Documentation

Documentation can be found at <https://innmind.org/CLI/>.
