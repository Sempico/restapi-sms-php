<?php

declare(strict_types=1);

namespace Sempico\RestApi\Yii2;

use Sempico\RestApi\ApiClient;
use Sempico\RestApi\Sempico;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Yii2 application component.
 *
 * config/web.php (and console.php):
 *
 *     'components' => [
 *         'sempico' => [
 *             'class' => \Sempico\RestApi\Yii2\SempicoComponent::class,
 *             'token' => $_ENV['SEMPICO_TOKEN'],
 *             'senderId' => 'MyBrand',
 *         ],
 *     ],
 *
 * Usage: Yii::$app->sempico->sendSms('380501234567', 'Hello!');
 *        Yii::$app->sempico->api->stat([...]);
 *
 * @property Sempico $api
 */
class SempicoComponent extends Component
{
    public string $token = '';
    public string $baseUrl = ApiClient::DEFAULT_BASE_URL;
    public int $timeout = 30;
    public int $connectTimeout = 10;
    public bool $verifySsl = true;

    /** Default Sender ID for sendSms() */
    public string $senderId = '';

    /** Security key of the account, checked in the API_KEY header of DLR webhooks */
    public string $webhookKey = '';

    private ?Sempico $api = null;

    public function getApi(): Sempico
    {
        return $this->api ??= new Sempico($this->token, [
            'base_url'        => $this->baseUrl,
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify_ssl'      => $this->verifySsl,
        ]);
    }

    /**
     * Replace the client, e.g. with a fake one in tests.
     */
    public function setApi(Sempico $api): void
    {
        $this->api = $api;
    }

    /**
     * Send SMS with the default Sender ID.
     *
     * @param string|string[] $numbers
     */
    public function sendSms(string|array $numbers, string $text, ?string $senderId = null, array $options = []): mixed
    {
        $senderId ??= $this->senderId;
        if ($senderId === '') {
            throw new InvalidConfigException('Sender ID is not set: configure "senderId" of the sempico component.');
        }

        return $this->getApi()->sendSms($numbers, $senderId, $text, $options);
    }

    /**
     * Proxy all other calls to the Sempico client: Yii::$app->sempico->me(), ->stat([...])...
     */
    public function __call($name, $params)
    {
        if (method_exists(Sempico::class, $name)) {
            return $this->getApi()->$name(...$params);
        }

        return parent::__call($name, $params);
    }
}
