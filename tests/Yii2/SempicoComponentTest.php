<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Yii2;

use Composer\InstalledVersions;
use PHPUnit\Framework\TestCase;
use Sempico\RestApi\Sempico;
use Sempico\RestApi\Tests\Support\FakeApiClient;
use Sempico\RestApi\Yii2\SempicoComponent;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;

/**
 * Runs only when yiisoft/yii2 is installed (see the "yii2" job in CI).
 */
final class SempicoComponentTest extends TestCase
{
    protected function setUp(): void
    {
        if (!InstalledVersions::isInstalled('yiisoft/yii2')) {
            self::markTestSkipped('yiisoft/yii2 is not installed');
        }
        if (!class_exists(\Yii::class, false)) {
            // Yii.php declares BaseYii itself, so it must be loaded before anything autoloads BaseYii
            require_once InstalledVersions::getInstallPath('yiisoft/yii2') . '/Yii.php';
        }
    }

    private function component(array $config = []): SempicoComponent
    {
        return \Yii::createObject(['class' => SempicoComponent::class, 'token' => 'yii-token', 'baseUrl' => 'http://sempico.test'] + $config);
    }

    public function testApiIsConfigured(): void
    {
        $component = $this->component(['timeout' => 5]);

        self::assertInstanceOf(Sempico::class, $component->api);
        self::assertSame($component->api, $component->getApi());
        self::assertSame('yii-token', $component->api->getClient()->getToken());
        self::assertSame('http://sempico.test/', $component->api->getClient()->getDomain());
    }

    public function testSendSmsRequiresSender(): void
    {
        $this->expectException(InvalidConfigException::class);

        $this->component()->sendSms('380501234567', 'Hi');
    }

    public function testUnknownMethod(): void
    {
        $this->expectException(UnknownMethodException::class);

        $this->component()->doesNotExist();
    }

    public function testSendSmsWithDefaultSender(): void
    {
        $fake = new FakeApiClient('t');
        $component = $this->component(['senderId' => 'Brand']);
        $component->setApi(new Sempico($fake));

        $component->sendSms('380501234567', 'Hi');
        self::assertSame(['number' => ['380501234567'], 'senderID' => 'Brand', 'text' => 'Hi'], $fake->lastRequest()['data']);

        $component->sendSms(['1', '2'], 'Hi', 'Other', ['type' => 'flashsms']);
        self::assertSame(['number' => ['1', '2'], 'senderID' => 'Other', 'text' => 'Hi', 'type' => 'flashsms'], $fake->lastRequest()['data']);
    }

    public function testProxiesToSempico(): void
    {
        $fake = (new FakeApiClient('t'))->willReturn(['status' => true, 'data' => ['balance_units' => 3]]);
        $component = $this->component();
        $component->setApi(new Sempico($fake));

        self::assertSame(['balance_units' => 3], $component->me());
        $component->blackListAdd(['1']);
        self::assertSame(['route' => 'v1/black-list-add', 'method' => 'POST', 'data' => ['numbers' => ['1']]], $fake->lastRequest());
    }
}
