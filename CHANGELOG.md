# Changelog

## Unreleased

### Added
- PHPUnit tests: HTTP client against a local fake API, all endpoints, v1.0 classes, DLR helper, Laravel (Testbench) and Yii2 integrations.
- GitHub Actions CI: PHP 8.1–8.5, Laravel 10–13, Yii2, PHPStan level 6.
- `SempicoComponent::setApi()` (Yii2) to replace the client in tests.

### Changed
- Laravel channel throws a clear `LogicException` when a notification has no `toSempico()` method.
- Laravel notification channel is registered with a non-static closure (`Manager::extend()` binds it).

## 1.1.0 — 2026-11-26

### Added
- `Sempico` client: one entry point, returns the `data` of the answer and throws `ApiException`.
- `ApiException` with `getHttpCode()`, `getResponse()`, `isUnauthorized()`, `isRateLimited()`.
- OAuth 2.0: `ApiClient::authorize()`, `accessToken()`, `login()`, `logout()`, `setToken()`.
- `/stat` endpoint: `Sms::stat()`, `Sempico::stat()`.
- HLR helper `Sempico::hlr()`.
- `DeliveryReport` helper for the DLR webhook.
- Laravel: auto-discovered service provider, `Sempico` facade, `sempico` notification channel, `config/sempico.php`.
- Yii2: `Sempico\RestApi\Yii2\SempicoComponent`.
- Client options: `base_url`, `timeout`, `connect_timeout`, `verify_ssl`.
- `curlRequest()` result has a new `response` key with the decoded JSON.
- Guides for Laravel, Yii2, Symfony, CodeIgniter 4, Slim 4, WordPress.

### Changed
- TLS certificate verification is enabled by default (was disabled). Set `verify_ssl => false` only for debugging.
- Requests have timeouts (30 s, connect 10 s) instead of waiting forever.
- `Auth` reuses `ApiClient`; the error text is now taken from the `errors` field of the answer.
- `Additional::productPrice()`, `accountPrice()`, `countryPrice()` can be called without arguments.
- Requires PHP 8.1+, `ext-curl`, `ext-json`.
- README rewritten; all code examples are valid PHP now.

### Removed
- Unused dependency `curl/curl`.
- `version` field from `composer.json` (the version comes from git tags).
- `curl_close()` call (deprecated in PHP 8.5).
