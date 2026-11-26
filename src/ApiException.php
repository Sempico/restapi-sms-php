<?php

declare(strict_types=1);

namespace Sempico\RestApi;

use RuntimeException;

/**
 * Thrown by ApiClient::request() and the Sempico facade when a request fails
 * or the API answers with `{"status": false, "error_code": ..., "errors": ...}`.
 *
 * The code is the API `error_code` (400, 401, 403, 429) or the HTTP status.
 */
class ApiException extends RuntimeException
{
    /**
     * @param array $output raw output of ApiClient::curlRequest()
     */
    public function __construct(string $message, int $code = 0, private array $output = [])
    {
        parent::__construct($message, $code);
    }

    public function getHttpCode(): int
    {
        return (int) ($this->output['http_code'] ?? 0);
    }

    public function getResponse(): ?array
    {
        return $this->output['response'] ?? null;
    }

    public function isRateLimited(): bool
    {
        return $this->getCode() === 429;
    }

    public function isUnauthorized(): bool
    {
        return $this->getCode() === 401;
    }
}
