# CHANGELOG

## 4.0.1 - 2021-02-08

### Fixed

- Change date format of PDO logger to Y-m-d H:i:s
- Use createdOn as column name in PDO logger

## 4.0.0 - 2021-02-08

### Added

- Add PDO logger.

### Changed

- Param 2 of Handler constructor now requires LoggerInterface.

## 3.0.1 - 2020-05-19

### Fixed

- Update dependency from package `zkwbbr/utils` from `^1.0` to `^1.0 || ^2.0`.

## 3.0.0 - 2020-05-08

### Added

- Add setItemsToRemoveFromServerVar() service method.
- Add process() service method to explicitly process the error.

### Changed

- Use process() service method to explicitly process the error instead of the previous behavior which was done in the __construct().

## 2.1.0 - 2020-02-09

### Changed

- Make getErrorHash() method public in Handler class so external services can use it.

## 2.0.1 - 2020-02-08

### Fixed

- Fix minor discrepancy in the timezone of the logs.

## 2.0.0 - 2019-08-14

### Changed

- Update dependency `metarush/email-fallback` to a major version (from v3 to v4).

## 1.0.0 - 2019-03-27

- Release first version.