# Changelog

## [Unreleased]

### Added

- `Innmind\CLI\Command\Usage`
- `Innmind\CLI\Command\Name`
- `Innmind\CLI\Commands::for()`

### Changed

- Requires `innmind/immutable:~5.16`
- Requires `innmind/operating-system:~6.0`
- `Innmind\CLI\Environment::read()` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Console::read()` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Console::output()` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Console::error()` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Environment::output()` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Environment::error()` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Question\Question` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Question\ChoiceQuestion` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Question\Question` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Question\ChoiceQuestion` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Commands` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Command` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Main::main()` now return an `Innmind\Immutable\Attempt`
- `Innmind\CLI\Command::usage()` now return a `Usage`
- `Innmind\CLI\Command\Arguments` constructor is now declared internal
- `Innmind\CLI\Command\Options` constructor is now declared internal
- `Innmind\CLI\Environment\ExitCode` constructor is now declared internal
- `Innmind\CLI\Question\Question` constructor is now private, use `::of()` instead
- `Innmind\CLI\Question\ChoiceQuestion` constructor is now private, use `::of()` instead

### Removed

- `Innmind\CLI\Exception\NoRequiredArgumentAllowedAfterAnOptionalOne`
- `Innmind\CLI\Exception\OnlyOnePackArgumentAllowed`
- `Innmind\CLI\Exception\PackArgumentMustBeTheLastOne`
- `Innmind\CLI\Exception\EmptyDeclaration`

### Fixed

- PHP `8.4` deprecations
- Using a deprecated method to parse options

## 3.6.0 - 2024-03-10

### Added

- Support for `innmind/operating-system:~5.0`

## 3.5.2 - 2023-11-01

### Fixed

- Matching commands with a name ending with `:`

## 3.5.1 - 2023-11-01

### Fixed

- Matching commands with a name ending with `:`

## 3.5.0 - 2023-11-01

### Changed

- Requires `innmind/operating-system:~4.0`

### Removed

- Support for `innmind/stream:~3.0`

## 3.4.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`

### Removed

- Support for PHP `8.1`

## 3.3.0 - 2023-01-29

### Added

- Support for `innmind/stream:~4.0`

## 3.2.0 - 2023-01-02

### Added

- `Innmind\CLI\Main` now accepts `Innmind\OperatingSystem\Config` as an argument to its constructor

### Changed

- Requires `innmind/operating-system:~3.4`
