# CHANGELOG

## Unreleased

* Internal modernization for PHP 8.4/8.5. No public API, namespace or behavior
  changes — this release is backward compatible.
* Added the `#[\NoDiscard]` attribute (PHP 8.5) to `ApexMiddleware::process()`,
  `ApexMiddlewareFactory::__invoke()`, `ConfigProvider::__invoke()` and
  `ConfigProvider::getDependencies()`, so consumers are now warned when the
  return value is accidentally discarded.
* Replaced `array_pop()` with `array_last()` (PHP 8.5) in `ApexMiddleware`,
  where the call was only reading the last array element.

## 3.0.0 - 2023-03-07

* Added support for PHP 8.1.
* Improved code to `phpstan` level `max`.
* Minor internal refactoring.
