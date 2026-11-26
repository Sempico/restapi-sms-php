<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Support;

use Sempico\RestApi\ApiClient;

/**
 * ApiClient without network: records the requests and returns queued answers.
 */
final class FakeApiClient extends ApiClient
{
    /** @var list<array{route: string, method: string|bool, data: array<mixed>}> */
    public array $requests = [];

    /** @var list<array<string, mixed>> */
    private array $queue = [];

    /**
     * Queue a decoded API answer (or the default success answer).
     *
     * @param array<string, mixed>|null $response
     */
    public function willReturn(?array $response = null, int $httpCode = 200, string $error = ''): self
    {
        $response ??= ['status' => true, 'data' => ['ok' => true]];
        $this->queue[] = [
            'result'        => json_encode($response),
            'http_code'     => $httpCode,
            'request_error' => $error,
            'response'      => $response,
        ];

        return $this;
    }

    public function curlRequest(string $url, string|bool $customMethod, array $data): mixed
    {
        $this->requests[] = ['route' => $url, 'method' => $customMethod, 'data' => $data];

        if ($this->queue === []) {
            $this->willReturn();
        }

        return array_shift($this->queue);
    }

    /**
     * @return array{route: string, method: string|bool, data: array<mixed>}
     */
    public function lastRequest(): array
    {
        return $this->requests[array_key_last($this->requests)];
    }
}
