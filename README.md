<p align="center">
  <a href="https://sempico.solutions">
    <img src="https://sempico.solutions/wp-content/uploads/2025/09/Logo.png" alt="Sempico Solutions" height="80">
  </a>
</p>

<h1 align="center">Sempico Solutions SMS REST API — PHP SDK</h1>

<p align="center">
  Official PHP library for the <a href="https://sempico.solutions">Sempico Solutions</a> A2P messaging platform:
  SMS, Flash SMS, HLR / MNP lookup, campaigns, groups of numbers, black list, statistics and prices.
</p>

<p align="center">
  <a href="https://packagist.org/packages/sempico/restapi-sms-php"><img src="https://img.shields.io/packagist/v/sempico/restapi-sms-php.svg" alt="Latest version"></a>
  <a href="https://packagist.org/packages/sempico/restapi-sms-php"><img src="https://img.shields.io/packagist/dt/sempico/restapi-sms-php.svg" alt="Downloads"></a>
  <a href="https://github.com/Sempico/restapi-sms-php/actions/workflows/tests.yml"><img src="https://github.com/Sempico/restapi-sms-php/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <img src="https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg" alt="PHP >= 8.1">
  <img src="https://img.shields.io/badge/Laravel-ready-ff2d20.svg" alt="Laravel">
  <img src="https://img.shields.io/badge/Yii2-ready-40b3d8.svg" alt="Yii2">
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="MIT"></a>
</p>

<p align="center">
  <a href="https://restapi.sempico.solutions/desc/">API reference</a> ·
  <a href="docs/laravel.md">Laravel</a> ·
  <a href="docs/yii2.md">Yii2</a> ·
  <a href="docs/symfony.md">Symfony</a> ·
  <a href="docs/other-frameworks.md">CodeIgniter · Slim · WordPress</a> ·
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Authorization](#authorization)
- [SMS](#sms)
- [Groups of numbers](#groups-of-numbers)
- [Black list](#black-list)
- [Statistic and prices](#statistic-and-prices)
- [Delivery reports (DLR webhook)](#delivery-reports-dlr-webhook)
- [Errors and rate limits](#errors-and-rate-limits)
- [Framework integrations](#framework-integrations)
- [Low-level API (v1.0 compatible)](#low-level-api-v10-compatible)

## Requirements

- PHP 8.1 or newer with the `curl` and `json` extensions
- A [Sempico Solutions](https://sempico.solutions) account with REST API enabled and an API token

## Installation

```bash
composer require sempico/restapi-sms-php
```

## Quick start

```php
use Sempico\RestApi\Sempico;
use Sempico\RestApi\ApiException;

$sempico = new Sempico('YOUR_API_TOKEN');

try {
    $result = $sempico->sendSms('380501234567', 'Sempico', 'Hello from Sempico Solutions!');
    // ['380501234567' => [['id_state' => 1855153903]]]

    $me = $sempico->me();
    echo $me['balance_units'] . ' ' . $me['currency'];
} catch (ApiException $e) {
    echo $e->getCode() . ': ' . $e->getMessage(); // 401: Invalid Access token
}
```

Every method of `Sempico` returns the `data` part of the API answer as an array
and throws `Sempico\RestApi\ApiException` when the API answers with an error.

Client options:

```php
$sempico = new Sempico('YOUR_API_TOKEN', [
    'base_url'        => 'https://restapi.sempico.solutions/', // default
    'timeout'         => 30,   // seconds
    'connect_timeout' => 10,   // seconds
    'verify_ssl'      => true, // keep it enabled in production
]);
```

## Authorization

Use the token from the dashboard, or get it with login and password (OAuth 2.0: `/authorize` → `/access-token`):

```php
$sempico = new Sempico();

$token = $sempico->login('my_login', 'my_password');
// ['access_token' => '5c9a3e2f-...', 'expires_at' => '2026-07-08 12:00:00']
// the token is kept in the client and used for the next requests

$sempico->me();
$sempico->logout(); // destroy the token
```

Register a new customer:

```php
$sempico->register([
    'login'        => 'new_login',        // required
    'password'     => 'StrongPassword1',  // required
    'name'         => 'John Doe',         // required, responsible person
    'phone'        => '12345678901',      // required
    'email'        => 'john@example.com', // required
    'skype'        => 'john.doe',         // messenger
    'company_name' => 'ACME Inc',
]);
```

## SMS

```php
// one text to several numbers (up to 100 numbers in one request)
$sempico->sendSms(['12345678901', '12345678902'], 'Sempico', "Hello\nWorld!", [
    'type'         => 'sms',          // sms, flashsms, wappush, vcard, hlr, mnp
    'beginDate'    => '2026-12-01',   // scheduled date (YYYY-MM-DD)
    'beginTime'    => '11:00',        // scheduled time (HH:MM)
    'lifetime'     => 86400,          // validity period, seconds
    'delivery'     => true,           // send DLR to callback_url
    'callback_url' => 'https://example.com/sempico/dlr',
    'campaign'     => 'black-friday', // campaign name for the statistic
]);

// different messages in one request
$sempico->send([
    ['number' => ['12345678901'], 'senderID' => 'SMS', 'text' => 'Hello!'],
    ['number' => ['12345678902', '12345678900'], 'senderID' => 'SMSSend', 'text' => 'How are you?'],
]);

// HLR lookup
$sempico->hlr(['12345678901', '12345678902']);

// campaign to the numbers of groups
$sempico->sendBulk([
    'senderID'          => 'SMS',                                       // required
    'text'              => 'Hello #first_name#, we send SMS to #number#', // required
    'id_group'          => [13],                                        // required
    'id_group_excluded' => [24, 26],
    'phone'             => ['12345678822'],
    'dateStart'         => '2026-12-01',
    'timeStart'         => '10:00',
    'timeStop'          => '18:00',
]);

// info about sent SMS (last few days)
$sempico->smsInfo([
    'id_state'    => [1855153903],
    'phone'       => ['12345678901'],
    'senderID'    => 'Sempico',
    'state'       => ['DELIVERD', 'UNDELIV'],
    'time_period' => '2026-12-01 00:00:00 - 2026-12-01 23:59:59',
    'limit'       => 100,
    'offset'      => 0,
]);

// check which sender / text will be really used for the numbers
$sempico->replacement(['number' => ['12345678901'], 'senderID' => 'Sempico', 'text' => 'Hello']);
```

## Groups of numbers

```php
$sempico->groups(['limit' => 10, 'offset' => 0]);

$sempico->createGroup('Customers', [
    'on_birth'         => true,
    'time_birth'       => '10:00',
    'originator_birth' => 'Sempico',
    'text_birth'       => 'Happy birthday, #first_name#!',
]);

$sempico->addNumbersToGroup(13, [
    ['number' => '12345678901', 'name' => 'Logan', 'surname' => 'Smith', 'date_birth' => '1992-12-31', 'male' => 'm', 'note_1' => 'VIP'],
    '12345678902', // a plain phone is fine too
]);

$sempico->searchNumbersInGroup(13, ['12345678901']);
$sempico->deleteNumbersFromGroup(13, ['12345678901']);
$sempico->deleteGroups([13, 14]);
```

## Black list

```php
$sempico->blackListAdd(['12345665777', '12345665772']); // up to 1000 numbers
$sempico->blackListSearch(['12345665777']);
$sempico->blackListDelete(['12345665777']);
```

## Statistic and prices

```php
$sempico->stat([
    'date_range'    => '2026-11-01 00:00:00 - 2026-11-30 23:59:59',
    'groups_choose' => ['day', 'mcc', 'mnc'],
    'type_sms'      => ['sms'],
]);

$sempico->accountPrice(['ISO' => 'UA']);   // your prices
$sempico->productPrice(['MCC' => '255']);  // prices by products
$sempico->countryPrice(['ISO' => 'US']);
$sempico->countryByContinent();
```

## Delivery reports (DLR webhook)

Sempico Solutions sends a `POST` with a JSON array of reports to `callback_url`
(or to the URL from the dashboard). Fields: `id_state`, `originator`, `phone`, `type_sms`,
`text_sms`, `num_parts`, `part_no`, `state` (`DELIVRD`, `UNDELIV`, `EXPIRED`, `ENROUTE`),
`time`, optional `price`, `error`, `hlr`.

```php
use Sempico\RestApi\DeliveryReport;

if (!DeliveryReport::isValidKey($_SERVER['HTTP_API_KEY'] ?? null, 'YOUR_SECURITY_KEY')) {
    http_response_code(403);
    exit;
}

$reports = DeliveryReport::parse(file_get_contents('php://input'));
foreach ($reports as $report) {
    // update the message status in your DB by $report['id_state']
}

http_response_code(200);
// with the confirmation mode enabled in the account:
echo DeliveryReport::confirm($reports); // [1855153903, ...]
```

Answer with HTTP 200 quickly: a report is sent no more than 2 times (the second attempt after ~30 seconds).

## Errors and rate limits

```php
try {
    $sempico->sendSms('12345678901', 'Sempico', 'Hi');
} catch (ApiException $e) {
    $e->getCode();        // error_code: 400, 401, 403, 429
    $e->getMessage();     // "Invalid Access token", "IP is not in WhiteList", ...
    $e->getHttpCode();
    $e->getResponse();    // full decoded answer
    $e->isUnauthorized(); // 401 — token is wrong or expired
    $e->isRateLimited();  // 429 — see the Retry-After header
}
```

| Method | Limit (per IP) |
|---|---|
| `register` | 2 requests per hour |
| `me`, `stat`, `smsInfo` | 1 request per 15 minutes |
| `send` | up to 100 phone numbers in one request, requests must be synchronous |

## Framework integrations

| Framework | What is included | Guide |
|---|---|---|
| **Laravel** 10–13 | Auto-discovered service provider, `Sempico` facade, `sempico` notification channel, config | [docs/laravel.md](docs/laravel.md) |
| **Yii2** | Application component `Yii::$app->sempico` | [docs/yii2.md](docs/yii2.md) |
| **Symfony** 6–7 | Service configuration, autowiring, Notifier-style usage | [docs/symfony.md](docs/symfony.md) |
| **CodeIgniter 4**, **Slim 4**, **WordPress**, plain PHP | Ready-to-copy snippets | [docs/other-frameworks.md](docs/other-frameworks.md) |

## Low-level API (v1.0 compatible)

The classes from v1.0 still work unchanged. They return the raw answer:
`['result' => string, 'http_code' => int, 'request_error' => string, 'response' => ?array]`.

```php
use Sempico\RestApi\{ApiClient, Sms, NumbersGroup, BlackList, Additional, Auth};

$client = new ApiClient('YOUR_API_TOKEN');

$client->getInfoAboutMe();

$sms = new Sms($client);
$sms->send(['number' => ['12345678901'], 'senderID' => 'SMS', 'text' => 'Hello']);
$sms->sendBulk([...]);
$sms->getInfo([...]);
$sms->refactoring([...]); // /replacement
$sms->stat([...]);

$group = new NumbersGroup($client);
$group->getAll([...]); $group->create([...]); $group->delete(['id_group' => [1, 2]]);
$group->addNumber([...]); $group->searchNumber([...]); $group->deleteNumber([...]);

$blackList = new BlackList($client);
$blackList->add(['numbers' => ['12345665777']]);
$blackList->search(['numbers' => ['12345665777']]);
$blackList->delete(['numbers' => ['12345665777']]);

$additional = new Additional($client);
$additional->productPrice(); $additional->accountPrice(['ISO' => 'US']);
$additional->countryPrice(['MCC' => 310]); $additional->countryByContinent();

$auth = new Auth();
$auth->register([...]); // returns a JSON string
```

## Development

```bash
composer install
composer test      # PHPUnit: unit tests with a local fake API + Laravel integration
composer analyse   # PHPStan level 6
```

CI runs the tests on PHP 8.1–8.5 with Laravel 10–13 and Yii2, see [`.github/workflows/tests.yml`](.github/workflows/tests.yml).
The tests never call the real API.

## Support

- API reference: <https://restapi.sempico.solutions/desc/>
- Website: <https://sempico.solutions>
- Email: <support@sempico.solutions>

## License

MIT © [Sempico Solutions](https://sempico.solutions). See [LICENSE.md](LICENSE.md).
