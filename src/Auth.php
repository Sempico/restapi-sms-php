<?php

declare(strict_types=1);

namespace Sempico\RestApi;

class Auth
{
    private ApiClient $client;

    public function __construct(?ApiClient $client = null)
    {
        $this->client = $client ?? new ApiClient();
    }

    /**
     * Register new customer
     *
     * @param array $config login, password, name, phone, email (required); skype, company_name
     * @return string JSON: {"result": <decoded API answer>, "http_code": int, "error": string}
     */
    public function register(array $config)
    {
        return $this->curlRequest('v1/register', 'POST', $config);
    }

    /**
     * Make curl request
     * @param string $url
     * @param string|bool $customMethod
     * @param array $data
     * @return mixed
     */
    public function curlRequest(string $url, string|bool $customMethod, array $data): mixed
    {
        $output = $this->client->curlRequest($url, $customMethod, $data);

        return $this->handleError([
            'result'    => $output['result'],
            'http_code' => $output['http_code'],
            'error'     => $output['request_error'],
        ]);
    }

    /**
     * Handle result for errors
     * @param array $data
     * @return mixed
     */
    protected function handleError(array $data): mixed
    {
        if (!strlen($data['error'])) {
            $data['result'] = is_string($data['result']) ? json_decode($data['result']) : null;
            if (isset($data['result']->error_code) && $data['result']->error_code) {
                $errors = $data['result']->errors ?? $data['result']->error ?? '';
                $data['error'] = is_string($errors) ? $errors : json_encode($errors);
            }
        }

        return json_encode($data);
    }
}
