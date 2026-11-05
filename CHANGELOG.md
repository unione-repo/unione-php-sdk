# Changelog

All notable changes of this package. Versions follow [Semantic Versioning](https://semver.org/).

## [2.3.0](https://github.com/unione-repo/unione-php-sdk/releases/tag/v2.3.0) - 2026-11-05

### Added

- Tests of the package (offline: the API is faked, no request leaves the machine).
- A CI workflow (`.github/workflows/test.yml`) runs the tests on every push and pull request over the supported runtimes; a release is published only when they pass.
- `ApiException` tells what failed and how to act: `getMethod()` (the API method, `email/send`), `getKind()` (`api`, `network`, `timeout`, `invalid_response`, `invalid_request`), `isRetryable()` (a repeat may succeed: a network error, a timeout, HTTP 429 or 5xx), `getHint()` (how to fix the API error, from the errors table of the docs) and `getApiMessage()` (the message of the API as is).
- Checks before sending (kind `invalid_request`, no request is sent): an empty API key; for `email/send` a missing or empty `message`, `message.recipients` or `email` of a recipient.

### Changed

- **The text of the error message** names the method and, for an API error, the code, the HTTP status and the fix: `email/send: Unknown recipient (API error 204, HTTP 400). Fix: ... See ...#api-errors`. Code that parsed the message should use the error code and `getApiMessage()`.
- A text of the server in the message is cut to 500 characters (`getApiMessage()` keeps all of it).
- An `ApiException` thrown by a custom transport keeps its code, HTTP status, response and kind (it was turned into a new network error).

### Fixed

- The API error code is taken only when it is a number (or a string of digits).
- Timeouts of PSR-18 clients that say "timeout reached" / "max duration" (Symfony HttpClient) are kind `timeout`.
- The examples of the docs print the error code once.

Earlier releases: the [tags](https://github.com/unione-repo/unione-php-sdk/tags) of the repository.
