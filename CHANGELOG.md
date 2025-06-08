# Changelog

## [Unreleased]

### Changed

- Requires `innmind/immutable:~5.16`
- Requires `innmind/operating-system:~6.0`
- `Innmind\Cli\Environment::read()` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Console::read()` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Console::output()` now return an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Console::error()` now return an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Environment::output()` now return an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Environment::error()` now return an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Question\Question` now return the read data in an `Innmind\Immutable\Attempt`
- `Innmind\Cli\Question\ChoiceQuestion` now return the read data in an `Innmind\Immutable\Attempt`

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
