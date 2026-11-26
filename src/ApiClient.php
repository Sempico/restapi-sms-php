<?php

declare(strict_types=1);

namespace Sempico\RestApi;

/**
 * Low-level HTTP client for the Sempico Solutions REST API.
 *
 * @see https://restapi.sempico.solutions/desc/
 */
class ApiClient
{
    public const DEFAULT_BASE_URL = 'https://restapi.sempico.solutions/';

    /**
     * REST API access token (sent in the X-Access-Token header).
     */
    private string $token;

    /**
     * Base URL of the API, always ends with "/".
     */
    private string $domain;

    /**
     * Request timeout, seconds.
     */
    private int $timeout;

    /**
     * Connection timeout, seconds.
     */
    private int $connectTimeout;

    /**
     * Verify the TLS certificate of the API host.
     */
    private bool $verifySsl;

    /**
     * @param string $token   REST API access token. Can be empty when it is going to be obtained with login().
     * @param array{
     *     base_url?: string,
     *     timeout?: int,
     *     connect_timeout?: int,
     *     verify_ssl?: bool
     * } $options
     */
    public function __construct(string $token = '', array $options = [])
    {
        $this->token = $token;
        $this->domain = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/') . '/';
        $this->timeout = (int) ($options['timeout'] ?? 30);
        $this->connectTimeout = (int) ($options['connect_timeout'] ?? 10);
        $this->verifySsl = (bool) ($options['verify_ssl'] ?? true);
    }

    /**
     * Get api token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set api token (for example, after login()).
     */
    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get api domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Make a request to the API.
     *
     * The result has the keys:
     * - `result`        raw response body (string) or false on transport error
     * - `http_code`     HTTP status code
     * - `request_error` cURL error message (empty string when there is none)
     * - `response`      decoded JSON body as an associative array, or null
     *
     * @param string      $url          route relative to the base URL, e.g. "v1/send"
     * @param string|bool $customMethod HTTP method ("POST", "GET"...); false means GET without a body
     * @param array       $data         request body, sent as JSON
     * @return array{result: string|false, http_code: int, request_error: string, response: array|null}
     */
    public function curlRequest(string $url, string|bool $customMethod, array $data): mixed
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($this->token !== '') {
            $headers[] = 'X-Access-Token: ' . $this->token;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->domain . ltrim($url, '/'),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_USERAGENT      => 'sempico-restapi-sms-php',
        ]);

        if ($customMethod && strtoupper((string) $customMethod) !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper((string) $customMethod));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $body = curl_exec($ch);
        $decoded = is_string($body) ? json_decode($body, true) : null;

        return [
            'result'        => $body,
            'http_code'     => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'request_error' => curl_error($ch),
            'response'      => is_array($decoded) ? $decoded : null,
        ];
    }

    /**
     * Make a request and return the `data` part of a successful answer.
     *
     * @throws ApiException when the request fails or the API answers with `status: false`
     */
    public function request(string $route, string $method = 'POST', array $data = []): mixed
    {
        $output = $this->curlRequest($route, $method, $data);

        if ($output['request_error'] !== '') {
            throw new ApiException($output['request_error'], 0, $output);
        }

        $response = $output['response'];
        if (!is_array($response) || ($response['status'] ?? false) !== true) {
            $errors = $response['errors'] ?? $response['error'] ?? 'Unexpected API response';
            $message = is_string($errors) ? $errors : json_encode($errors, JSON_UNESCAPED_UNICODE);
            $code = (int) ($response['error_code'] ?? $output['http_code']);

            throw new ApiException((string) $message, $code, $output);
        }

        return $response['data'] ?? null;
    }

    /**
     * Step 1 of OAuth 2.0: get an authorization code by login and password.
     */
    public function authorize(string $username, string $password): array
    {
        return $this->curlRequest('v1/authorize', 'POST', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * Step 2 of OAuth 2.0: exchange the authorization code for an access token.
     */
    public function accessToken(string $authorizationCode): array
    {
        return $this->curlRequest('v1/access-token', 'POST', [
            'authorization_code' => $authorizationCode,
        ]);
    }

    /**
     * Authorize with login and password, get an access token and keep it in this client.
     *
     * @return array{access_token: string, expires_at: string}
     * @throws ApiException
     */
    public function login(string $username, string $password): array
    {
        $code = $this->request('v1/authorize', 'POST', [
            'username' => $username,
            'password' => $password,
        ]);
        $token = $this->request('v1/access-token', 'POST', [
            'authorization_code' => $code['authorization_code'] ?? '',
        ]);

        $this->setToken((string) ($token['access_token'] ?? ''));

        return $token;
    }

    /**
     * Destroy the current access token.
     */
    public function logout(): array
    {
        return $this->curlRequest('v1/logout', 'GET', []);
    }

    /**
     * Get info about account
     */
    public function getInfoAboutMe(): array
    {
        return $this->curlRequest('v1/me', false, []);
    }
}
