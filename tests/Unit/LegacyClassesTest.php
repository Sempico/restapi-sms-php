<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sempico\RestApi\Additional;
use Sempico\RestApi\Auth;
use Sempico\RestApi\BlackList;
use Sempico\RestApi\NumbersGroup;
use Sempico\RestApi\Sms;
use Sempico\RestApi\Tests\Support\FakeApiClient;

/**
 * The v1.0 API must keep its routes and the raw result format.
 */
final class LegacyClassesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, string, list<mixed>, string, array<mixed>}>
     */
    public static function methods(): iterable
    {
        $p = ['x' => 1];

        yield 'Sms::send' => [Sms::class, 'send', [$p], 'v1/send', $p];
        yield 'Sms::sendBulk' => [Sms::class, 'sendBulk', [$p], 'v1/send-bulk', $p];
        yield 'Sms::getInfo' => [Sms::class, 'getInfo', [$p], 'v1/sms-full-data', $p];
        yield 'Sms::refactoring' => [Sms::class, 'refactoring', [$p], 'v1/replacement', $p];
        yield 'Sms::stat' => [Sms::class, 'stat', [], 'v1/stat', []];
        yield 'NumbersGroup::getAll' => [NumbersGroup::class, 'getAll', [$p], 'v1/group', $p];
        yield 'NumbersGroup::create' => [NumbersGroup::class, 'create', [$p], 'v1/group-create', $p];
        yield 'NumbersGroup::delete' => [NumbersGroup::class, 'delete', [$p], 'v1/group-delete', $p];
        yield 'NumbersGroup::addNumber' => [NumbersGroup::class, 'addNumber', [$p], 'v1/group-number-add', $p];
        yield 'NumbersGroup::searchNumber' => [NumbersGroup::class, 'searchNumber', [$p], 'v1/group-number-search', $p];
        yield 'NumbersGroup::deleteNumber' => [NumbersGroup::class, 'deleteNumber', [$p], 'v1/group-number-delete', $p];
        yield 'NumbersGroup::sendBulk' => [NumbersGroup::class, 'sendBulk', [$p], 'v1/send-bulk', $p];
        yield 'BlackList::add' => [BlackList::class, 'add', [$p], 'v1/black-list-add', $p];
        yield 'BlackList::search' => [BlackList::class, 'search', [$p], 'v1/black-list-search', $p];
        yield 'BlackList::delete' => [BlackList::class, 'delete', [$p], 'v1/black-list-delete', $p];
        yield 'Additional::productPrice' => [Additional::class, 'productPrice', [], 'v1/product-price', []];
        yield 'Additional::accountPrice' => [Additional::class, 'accountPrice', [$p], 'v1/account-price', $p];
        yield 'Additional::countryPrice' => [Additional::class, 'countryPrice', [], 'v1/country-price', []];
        yield 'Additional::countryByContinent' => [Additional::class, 'countryByContinent', [], 'v1/country-by-continent', []];
    }

    /**
     * @param class-string $class
     * @param list<mixed> $args
     * @param array<mixed> $payload
     */
    #[DataProvider('methods')]
    public function testRoute(string $class, string $method, array $args, string $route, array $payload): void
    {
        $client = new FakeApiClient('token');
        $result = (new $class($client))->$method(...$args);

        self::assertSame(['route' => $route, 'method' => 'POST', 'data' => $payload], $client->lastRequest());
        self::assertSame(['result', 'http_code', 'request_error', 'response'], array_keys($result));
    }

    public function testGetInfoAboutMeIsGet(): void
    {
        $client = new FakeApiClient('token');
        $client->getInfoAboutMe();

        // FakeApiClient overrides curlRequest, so check the route and method passed to it
        self::assertSame(['route' => 'v1/me', 'method' => false, 'data' => []], $client->lastRequest());
    }

    public function testAuthRegisterReturnsJsonString(): void
    {
        $client = (new FakeApiClient())->willReturn(['status' => true, 'data' => ['info' => 'Account registered successfully']]);

        $json = (new Auth($client))->register(['login' => 'a']);
        $result = json_decode($json, true);

        self::assertSame(['route' => 'v1/register', 'method' => 'POST', 'data' => ['login' => 'a']], $client->lastRequest());
        self::assertSame(200, $result['http_code']);
        self::assertSame('', $result['error']);
        self::assertSame('Account registered successfully', $result['result']['data']['info']);
    }

    public function testAuthTakesErrorTextFromErrorsField(): void
    {
        $client = (new FakeApiClient())->willReturn(['status' => false, 'error_code' => 400, 'errors' => 'Login cannot be blank.'], 400);

        $result = json_decode((new Auth($client))->register([]), true);

        self::assertSame(400, $result['http_code']);
        self::assertSame('Login cannot be blank.', $result['error']);
    }

    public function testAuthKeepsTransportError(): void
    {
        $client = (new FakeApiClient())->willReturn(null, 0, 'Could not resolve host');

        $result = json_decode((new Auth($client))->register([]), true);

        self::assertSame('Could not resolve host', $result['error']);
    }
}
