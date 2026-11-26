<?php

declare(strict_types=1);

namespace Sempico\RestApi;

/**
 * Single entry point to the Sempico Solutions REST API.
 *
 * Every method returns the `data` part of the API answer as an array
 * and throws ApiException on an error, so no manual checking of
 * `http_code` / `status` is needed.
 *
 *     $sempico = new Sempico('your-token');
 *     $result  = $sempico->sendSms('380501234567', 'Sempico', 'Hello!');
 */
class Sempico
{
    private ApiClient $client;

    /**
     * @param string|ApiClient $token REST API token or a configured ApiClient
     * @param array            $options see ApiClient::__construct()
     */
    public function __construct(string|ApiClient $token = '', array $options = [])
    {
        $this->client = $token instanceof ApiClient ? $token : new ApiClient($token, $options);
    }

    public function getClient(): ApiClient
    {
        return $this->client;
    }

    // ---------------------------------------------------------------- auth

    /**
     * OAuth 2.0: get a token by login and password and use it for the next requests.
     *
     * @return array{access_token: string, expires_at: string}
     */
    public function login(string $username, string $password): array
    {
        return $this->client->login($username, $password);
    }

    public function logout(): mixed
    {
        return $this->client->request('v1/logout', 'GET');
    }

    /**
     * Register a new customer. Required: login, password, name, phone, email.
     */
    public function register(array $data): mixed
    {
        return $this->client->request('v1/register', 'POST', $data);
    }

    /**
     * Account info: login, name, balance_units, currency...
     */
    public function me(): mixed
    {
        return $this->client->request('v1/me', 'GET');
    }

    // ---------------------------------------------------------------- sms

    /**
     * Send one message to one or several numbers.
     *
     * @param string|string[] $numbers
     * @param array $options type, beginDate, beginTime, lifetime, callback_url, delivery, campaign, shortener
     * @return array phone => [['id_state' => ...]] or [['error' => ...]]
     */
    public function sendSms(string|array $numbers, string $senderId, string $text, array $options = []): mixed
    {
        return $this->send(['number' => array_values((array) $numbers), 'senderID' => $senderId, 'text' => $text] + $options);
    }

    /**
     * Send messages with the raw /send payload: one message array or a list of them.
     */
    public function send(array $messages): mixed
    {
        return $this->client->request('v1/send', 'POST', $messages);
    }

    /**
     * HLR lookup of the numbers.
     *
     * @param string|string[] $numbers
     */
    public function hlr(string|array $numbers, string $senderId = 'HLR', array $options = []): mixed
    {
        return $this->sendSms($numbers, $senderId, '__HLR__', ['type' => 'hlr'] + $options);
    }

    /**
     * Send SMS to the numbers of Groups. Required: senderID, text, id_group.
     */
    public function sendBulk(array $data): mixed
    {
        return $this->client->request('v1/send-bulk', 'POST', $data);
    }

    /**
     * Info about sent SMS: filters MCC, MNC, senderID, phone, id_state, state, time_period, limit, offset...
     */
    public function smsInfo(array $filters = []): mixed
    {
        return $this->client->request('v1/sms-full-data', 'POST', $filters);
    }

    /**
     * Check the replacement rules of the sender and text for the numbers.
     */
    public function replacement(array $data): mixed
    {
        return $this->client->request('v1/replacement', 'POST', $data);
    }

    /**
     * General statistic: groups_choose, destination, date_range, type_sms, id_name_delivery.
     */
    public function stat(array $filters = []): mixed
    {
        return $this->client->request('v1/stat', 'POST', $filters);
    }

    // ---------------------------------------------------------------- groups

    public function groups(array $filters = []): mixed
    {
        return $this->client->request('v1/group', 'POST', $filters);
    }

    public function createGroup(string $name, array $options = []): mixed
    {
        return $this->client->request('v1/group-create', 'POST', ['name_group' => $name] + $options);
    }

    /**
     * @param int[] $groupIds
     */
    public function deleteGroups(array $groupIds): mixed
    {
        return $this->client->request('v1/group-delete', 'POST', ['id_group' => array_values($groupIds)]);
    }

    /**
     * @param array $numbers list of ['number' => '...', 'name' => '...', ...] or plain phone strings
     */
    public function addNumbersToGroup(int $groupId, array $numbers): mixed
    {
        $numbers = array_map(static fn ($n) => is_array($n) ? $n : ['number' => (string) $n], array_values($numbers));

        return $this->client->request('v1/group-number-add', 'POST', ['id_group' => $groupId, 'numbers' => $numbers]);
    }

    public function searchNumbersInGroup(int $groupId, array $numbers): mixed
    {
        return $this->client->request('v1/group-number-search', 'POST', ['id_group' => $groupId, 'numbers' => self::phones($numbers)]);
    }

    public function deleteNumbersFromGroup(int $groupId, array $numbers): mixed
    {
        return $this->client->request('v1/group-number-delete', 'POST', ['id_group' => $groupId, 'numbers' => self::phones($numbers)]);
    }

    // ---------------------------------------------------------------- black list

    public function blackListAdd(array $numbers): mixed
    {
        return $this->client->request('v1/black-list-add', 'POST', ['numbers' => self::phones($numbers)]);
    }

    public function blackListSearch(array $numbers): mixed
    {
        return $this->client->request('v1/black-list-search', 'POST', ['numbers' => self::phones($numbers)]);
    }

    public function blackListDelete(array $numbers): mixed
    {
        return $this->client->request('v1/black-list-delete', 'POST', ['numbers' => self::phones($numbers)]);
    }

    // ---------------------------------------------------------------- prices

    /**
     * @param array $filters ISO, MCC, MNC
     */
    public function productPrice(array $filters = []): mixed
    {
        return $this->client->request('v1/product-price', 'POST', $filters);
    }

    /**
     * @param array $filters ISO, MCC, MNC
     */
    public function accountPrice(array $filters = []): mixed
    {
        return $this->client->request('v1/account-price', 'POST', $filters);
    }

    /**
     * @param array $filters ISO, MCC
     */
    public function countryPrice(array $filters = []): mixed
    {
        return $this->client->request('v1/country-price', 'POST', $filters);
    }

    public function countryByContinent(): mixed
    {
        return $this->client->request('v1/country-by-continent', 'POST');
    }

    /**
     * @return string[]
     */
    private static function phones(array $numbers): array
    {
        return array_map('strval', array_values($numbers));
    }
}
