<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Laravel;

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Orchestra\Testbench\TestCase;
use Sempico\RestApi\ApiClient;
use Sempico\RestApi\Laravel\Facades\Sempico as SempicoFacade;
use Sempico\RestApi\Laravel\SempicoMessage;
use Sempico\RestApi\Laravel\SempicoServiceProvider;
use Sempico\RestApi\Sempico;
use Sempico\RestApi\Tests\Support\FakeApiClient;

final class LaravelIntegrationTest extends TestCase
{
    private FakeApiClient $fake;

    protected function getPackageProviders($app): array
    {
        return [SempicoServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Sempico' => SempicoFacade::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sempico.token', 'config-token');
        $app['config']->set('sempico.base_url', 'http://sempico.test');
        $app['config']->set('sempico.sender_id', 'DefaultSender');
    }

    private function useFakeClient(): FakeApiClient
    {
        $this->fake = new FakeApiClient('config-token');
        $this->app->instance(ApiClient::class, $this->fake);
        $this->app->forgetInstance(Sempico::class);
        SempicoFacade::clearResolvedInstances();

        return $this->fake;
    }

    public function testConfigIsMerged(): void
    {
        self::assertSame(30, config('sempico.timeout'));
        self::assertTrue(config('sempico.verify_ssl'));
    }

    public function testClientIsBuiltFromConfig(): void
    {
        $client = $this->app->make(ApiClient::class);

        self::assertSame('config-token', $client->getToken());
        self::assertSame('http://sempico.test/', $client->getDomain());
        self::assertSame($client, $this->app->make(ApiClient::class), 'singleton');
    }

    public function testSempicoAliasAndSingleton(): void
    {
        self::assertInstanceOf(Sempico::class, $this->app->make('sempico'));
        self::assertSame($this->app->make(Sempico::class), $this->app->make('sempico'));
    }

    public function testFacade(): void
    {
        $fake = $this->useFakeClient();
        $fake->willReturn(['status' => true, 'data' => ['balance_units' => 5]]);

        self::assertSame(['balance_units' => 5], SempicoFacade::me());
        self::assertSame('v1/me', $fake->lastRequest()['route']);
    }

    public function testNotificationWithStringAndDefaultSender(): void
    {
        $fake = $this->useFakeClient();

        NotificationFacade::route('sempico', '380501234567')->notifyNow(new TextNotification());

        self::assertSame(
            ['route' => 'v1/send', 'method' => 'POST', 'data' => ['number' => ['380501234567'], 'senderID' => 'DefaultSender', 'text' => 'Hello']],
            $fake->lastRequest()
        );
    }

    public function testNotificationWithMessageAndModelRoute(): void
    {
        $fake = $this->useFakeClient();

        (new User())->notifyNow(new MessageNotification());

        self::assertSame(
            ['number' => ['380671112233'], 'senderID' => 'Brand', 'text' => 'Shipped', 'callback_url' => 'https://example.com/dlr'],
            $fake->lastRequest()['data']
        );
    }

    public function testNotificationIsSkippedWithoutPhone(): void
    {
        $fake = $this->useFakeClient();

        (new class () {
            use Notifiable;
        })->notifyNow(new TextNotification());

        self::assertSame([], $fake->requests);
    }

    public function testNotificationWithoutToSempicoFails(): void
    {
        $this->useFakeClient();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must have a toSempico($notifiable) method');

        NotificationFacade::route('sempico', '380501234567')->notifyNow(new NoSempicoNotification());
    }

    public function testConfigCanBePublished(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'sempico-config'])->assertSuccessful();

        self::assertFileExists(config_path('sempico.php'));
        @unlink(config_path('sempico.php'));
    }
}

final class TextNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['sempico'];
    }

    public function toSempico(object $notifiable): string
    {
        return 'Hello';
    }
}

final class MessageNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['sempico'];
    }

    public function toSempico(object $notifiable): SempicoMessage
    {
        return (new SempicoMessage())->text('Shipped')->from('Brand')->options(['callback_url' => 'https://example.com/dlr']);
    }
}

final class NoSempicoNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['sempico'];
    }
}

final class User
{
    use Notifiable;

    public function routeNotificationForSempico(): string
    {
        return '380671112233';
    }
}
