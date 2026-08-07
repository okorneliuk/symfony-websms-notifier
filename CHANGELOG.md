CHANGELOG
=========

## 1.2.5

 * Migrated SMS sending from the legacy simple GET endpoint to the JSON text-message API.
 * Messages are now sent as UTF-8 without ISO-8859-1 conversion.
 * Added message sender and provider-specific message options support.
 * Added PHPDoc literal-string types for content category, message type, and sender address type.
 * Added stricter HTTP and provider response handling.
 * Raised the minimum versions to PHP 8.2 and Symfony Notifier 7.4.
 * Updated package metadata, tests, and PHPUnit configuration to current Notifier bridge conventions.

## 1.2.4

 * Fixed workflow matrix.

## 1.2.3

 * Improved workflow matrix, small fixes.

## 1.2.2

 * Added support of Symfony 8.

## 1.2.1

 * Bumped PHP and Symfony dependencies.

## 1.1.3

 * Added support of Symfony 7.

## 1.1.2

 * Fixed variable.

## 1.1.1

 * Added an exception if there are wrong characters in the message.

## 1.1.0

 * Added support of Symfony 6.

## 1.0.1

 * Fixed encoding.
 * Updated readme.

## 1.0.0

 * Initial release.
