<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sempico\RestApi\ApiClient;
use Sempico\RestApi\ApiException;
use Sempico\RestApi\Sempico;
use Sempico\RestApi\Tests\Support\FakeApiClient;

final class SempicoTest extends TestCase
{
    private FakeApiClient $client;
    private Sempico $sempico;

    protected function setUp(): void
    {
        $this->client = new FakeApiClient('token');
        $this->sempico = new Sempico($this->client);
    }

    public function testCreatesClientFromToken(): void
    {
        $sempico = new Sempico('abc', ['base_url' => 'http://localhost']);

        self::assertInstanceOf(ApiClient::class, $sempico->getClient());
        self::assertSame('abc', $sempico->getClient()->getToken());
        self::assertSame('http://localhost/', $sempico->getClient()->getDomain());
    }

    public function testReturnsData(): void
    {
        $this->client->willReturn(['status' => true, 'data' => ['balance_units' => 10]]);

        self::assertSame(['balance_units' => 10], $this->sempico->me());
    }

    public function testThrowsOnApiError(): void
    {
        $this->client->willReturn(['status' => false, 'error_code' => 403, 'errors' => 'IP is not in WhiteList'], 403);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('IP is not in WhiteList');

        $this->sempico->me();
    }

    public function testNonStringErrorsAreEncoded(): void
    {
        $this->client->willReturn(['status' => false, 'error_code' => 400, 'errors' => ['login' => 'Login cannot be blank.']], 400);

        $this->expectExceptionMessage('{"login":"Login cannot be blank."}');

        $this->sempico->register([]);
    }

    /**
     * @return iterable<string, array{callable(Sempico): mixed, string, string, array<mixed>}>
     */
    public static function endpoints(): iterable
    {
        yield 'me' => [fn (Sempico $s) => $s->me(), 'v1/me', 'GET', []];
        yield 'logout' => [fn (Sempico $s) => $s->logout(), 'v1/logout', 'GET', []];
        yield 'register' => [fn (Sempico $s) => $s->register(['login' => 'a']), 'v1/register', 'POST', ['login' => 'a']];
        yield 'sendSms one number' => [
            fn (Sempico $s) => $s->sendSms('380501234567', 'Sempico', 'Hi'),
            'v1/send', 'POST', ['number' => ['380501234567'], 'senderID' => 'Sempico', 'text' => 'Hi'],
        ];
        yield 'sendSms options' => [
            fn (Sempico $s) => $s->sendSms(['k' => '1', '2'], 'S', 'T', ['type' => 'flashsms', 'campaign' => 'c']),
            'v1/send', 'POST', ['number' => ['1', '2'], 'senderID' => 'S', 'text' => 'T', 'type' => 'flashsms', 'campaign' => 'c'],
        ];
        yield 'sendSms options do not override required fields' => [
            fn (Sempico $s) => $s->sendSms('1', 'S', 'T', ['text' => 'other']),
            'v1/send', 'POST', ['number' => ['1'], 'senderID' => 'S', 'text' => 'T'],
        ];
        yield 'send raw list' => [
            fn (Sempico $s) => $s->send([['number' => ['1'], 'senderID' => 'S', 'text' => 'T']]),
            'v1/send', 'POST', [['number' => ['1'], 'senderID' => 'S', 'text' => 'T']],
        ];
        yield 'hlr' => [
            fn (Sempico $s) => $s->hlr(['1', '2']),
            'v1/send', 'POST', ['number' => ['1', '2'], 'senderID' => 'HLR', 'text' => '__HLR__', 'type' => 'hlr'],
        ];
        yield 'sendBulk' => [fn (Sempico $s) => $s->sendBulk(['id_group' => [1]]), 'v1/send-bulk', 'POST', ['id_group' => [1]]];
        yield 'smsInfo' => [fn (Sempico $s) => $s->smsInfo(['id_state' => [5]]), 'v1/sms-full-data', 'POST', ['id_state' => [5]]];
        yield 'replacement' => [fn (Sempico $s) => $s->replacement(['number' => ['1']]), 'v1/replacement', 'POST', ['number' => ['1']]];
        yield 'stat' => [fn (Sempico $s) => $s->stat(), 'v1/stat', 'POST', []];
        yield 'groups' => [fn (Sempico $s) => $s->groups(['limit' => 5]), 'v1/group', 'POST', ['limit' => 5]];
        yield 'createGroup' => [
            fn (Sempico $s) => $s->createGroup('Base', ['on_birth' => false]),
            'v1/group-create', 'POST', ['name_group' => 'Base', 'on_birth' => false],
        ];
        yield 'deleteGroups' => [fn (Sempico $s) => $s->deleteGroups([3 => 1, 4 => 2]), 'v1/group-delete', 'POST', ['id_group' => [1, 2]]];
        yield 'addNumbersToGroup' => [
            fn (Sempico $s) => $s->addNumbersToGroup(13, [['number' => '1', 'name' => 'A'], 380501234567]),
            'v1/group-number-add', 'POST', ['id_group' => 13, 'numbers' => [['number' => '1', 'name' => 'A'], ['number' => '380501234567']]],
        ];
        yield 'searchNumbersInGroup' => [
            fn (Sempico $s) => $s->searchNumbersInGroup(13, [1, '2']),
            'v1/group-number-search', 'POST', ['id_group' => 13, 'numbers' => ['1', '2']],
        ];
        yield 'deleteNumbersFromGroup' => [
            fn (Sempico $s) => $s->deleteNumbersFromGroup(13, ['1']),
            'v1/group-number-delete', 'POST', ['id_group' => 13, 'numbers' => ['1']],
        ];
        yield 'blackListAdd' => [fn (Sempico $s) => $s->blackListAdd([1]), 'v1/black-list-add', 'POST', ['numbers' => ['1']]];
        yield 'blackListSearch' => [fn (Sempico $s) => $s->blackListSearch(['1']), 'v1/black-list-search', 'POST', ['numbers' => ['1']]];
        yield 'blackListDelete' => [fn (Sempico $s) => $s->blackListDelete(['1']), 'v1/black-list-delete', 'POST', ['numbers' => ['1']]];
        yield 'productPrice' => [fn (Sempico $s) => $s->productPrice(['ISO' => 'US']), 'v1/product-price', 'POST', ['ISO' => 'US']];
        yield 'accountPrice' => [fn (Sempico $s) => $s->accountPrice(), 'v1/account-price', 'POST', []];
        yield 'countryPrice' => [fn (Sempico $s) => $s->countryPrice(['MCC' => '310']), 'v1/country-price', 'POST', ['MCC' => '310']];
        yield 'countryByContinent' => [fn (Sempico $s) => $s->countryByContinent(), 'v1/country-by-continent', 'POST', []];
    }

    /**
     * @param callable(Sempico): mixed $call
     * @param array<mixed> $payload
     */
    #[DataProvider('endpoints')]
    public function testEndpoint(callable $call, string $route, string $method, array $payload): void
    {
        $call($this->sempico);

        self::assertSame(['route' => $route, 'method' => $method, 'data' => $payload], $this->client->lastRequest());
    }

    public function testLoginKeepsToken(): void
    {
        $client = new FakeApiClient();
        $client
            ->willReturn(['status' => true, 'data' => ['authorization_code' => 'code-1', 'expires_at' => '2026-12-01 00:00:00']])
            ->willReturn(['status' => true, 'data' => ['access_token' => 'token-1', 'expires_at' => '2026-12-01 00:00:00']]);

        $result = (new Sempico($client))->login('user', 'pass');

        self::assertSame('token-1', $result['access_token']);
        self::assertSame('token-1', $client->getToken());
        self::assertSame(
            [
                ['route' => 'v1/authorize', 'method' => 'POST', 'data' => ['username' => 'user', 'password' => 'pass']],
                ['route' => 'v1/access-token', 'method' => 'POST', 'data' => ['authorization_code' => 'code-1']],
            ],
            $client->requests
        );
    }

    public function testFailedLoginKeepsOldToken(): void
    {
        $client = new FakeApiClient('old');
        $client->willReturn(['status' => false, 'error_code' => 400, 'errors' => 'Wrong login or password'], 400);

        try {
            (new Sempico($client))->login('user', 'bad');
            self::fail('ApiException expected');
        } catch (ApiException $e) {
            self::assertSame('Wrong login or password', $e->getMessage());
        }

        self::assertSame('old', $client->getToken());
        self::assertCount(1, $client->requests);
    }
}
