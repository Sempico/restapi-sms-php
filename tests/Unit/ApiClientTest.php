<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sempico\RestApi\ApiClient;
use Sempico\RestApi\ApiException;

/**
 * Real HTTP requests to a local fake API (PHP built-in server).
 */
final class ApiClientTest extends TestCase
{
    /** @var resource|null */
    private static $server;
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $port = self::freePort();
        self::$baseUrl = "http://127.0.0.1:{$port}/";

        self::$server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/../Support/server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );

        $deadline = microtime(true) + 5;
        while (!@fsockopen('127.0.0.1', $port) && microtime(true) < $deadline) {
            usleep(50_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }

    private function client(string $token = 'test-token', array $options = []): ApiClient
    {
        return new ApiClient($token, ['base_url' => self::$baseUrl] + $options);
    }

    public function testDefaults(): void
    {
        $client = new ApiClient('abc');

        self::assertSame('abc', $client->getToken());
        self::assertSame('https://restapi.sempico.solutions/', $client->getDomain());
    }

    public function testBaseUrlAlwaysEndsWithSlash(): void
    {
        self::assertSame('http://localhost/api/', (new ApiClient('', ['base_url' => 'http://localhost/api']))->getDomain());
        self::assertSame('http://localhost/api/', (new ApiClient('', ['base_url' => 'http://localhost/api///']))->getDomain());
    }

    public function testPostSendsJsonBodyAndToken(): void
    {
        $output = $this->client()->curlRequest('v1/send', 'POST', ['text' => 'Привіт / hello']);

        self::assertSame(['result', 'http_code', 'request_error', 'response'], array_keys($output), 'v1.0 result format');
        self::assertSame(200, $output['http_code']);
        self::assertSame('', $output['request_error']);
        self::assertIsString($output['result']);

        $echo = $output['response']['data'];
        self::assertSame('POST', $echo['method']);
        self::assertSame('/v1/send', $echo['path']);
        self::assertSame('test-token', $echo['token']);
        self::assertSame('application/json', $echo['content_type']);
        self::assertSame('sempico-restapi-sms-php', $echo['user_agent']);
        self::assertSame('{"text":"Привіт / hello"}', $echo['body']);
    }

    public function testFalseMethodMakesGetWithoutBody(): void
    {
        $echo = $this->client()->getInfoAboutMe()['response']['data'];

        self::assertSame('GET', $echo['method']);
        self::assertSame('/v1/me', $echo['path']);
        self::assertSame('', $echo['body']);
    }

    public function testLeadingSlashInRouteIsIgnored(): void
    {
        $echo = $this->client()->curlRequest('/v1/group', 'POST', [])['response']['data'];

        self::assertSame('/v1/group', $echo['path']);
    }

    public function testTokenHeaderIsNotSentWhenTokenIsEmpty(): void
    {
        $echo = $this->client('')->curlRequest('v1/register', 'POST', [])['response']['data'];

        self::assertNull($echo['token']);
    }

    public function testSetToken(): void
    {
        $client = $this->client('')->setToken('new-token');
        $echo = $client->curlRequest('v1/me', 'GET', [])['response']['data'];

        self::assertSame('new-token', $client->getToken());
        self::assertSame('new-token', $echo['token']);
    }

    public function testRequestReturnsData(): void
    {
        $data = $this->client()->request('v1/stat', 'POST', ['type_sms' => ['sms']]);

        self::assertSame('/v1/stat', $data['path']);
        self::assertSame('{"type_sms":["sms"]}', $data['body']);
    }

    public function testRequestThrowsApiErrors(): void
    {
        try {
            $this->client()->request('v1/unauthorized');
            self::fail('ApiException expected');
        } catch (ApiException $e) {
            self::assertSame(401, $e->getCode());
            self::assertSame(401, $e->getHttpCode());
            self::assertSame('Invalid Access token', $e->getMessage());
            self::assertTrue($e->isUnauthorized());
            self::assertFalse($e->isRateLimited());
            self::assertSame(false, $e->getResponse()['status']);
        }
    }

    public function testRequestThrowsOnRateLimit(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(429);

        $this->client()->request('v1/rate-limit');
    }

    public function testNonJsonAnswer(): void
    {
        $output = $this->client()->curlRequest('v1/not-json', 'POST', []);
        self::assertSame(502, $output['http_code']);
        self::assertNull($output['response']);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(502);
        $this->expectExceptionMessage('Unexpected API response');
        $this->client()->request('v1/not-json');
    }

    public function testTimeout(): void
    {
        $output = $this->client('t', ['timeout' => 1])->curlRequest('v1/slow', 'POST', []);

        self::assertFalse($output['result']);
        self::assertNotSame('', $output['request_error']);
    }

    public function testTransportErrorThrowsWithCodeZero(): void
    {
        $client = new ApiClient('t', ['base_url' => 'http://127.0.0.1:' . self::freePort(), 'connect_timeout' => 2]);

        try {
            $client->request('v1/me', 'GET');
            self::fail('ApiException expected');
        } catch (ApiException $e) {
            self::assertSame(0, $e->getCode());
            self::assertNotSame('', $e->getMessage());
            self::assertNull($e->getResponse());
        }
    }
}
