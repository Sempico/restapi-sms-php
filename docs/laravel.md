<p align="center"><a href="https://sempico.solutions"><img src="https://sempico.solutions/wp-content/uploads/2025/09/Logo.png" alt="Sempico Solutions" height="60"></a></p>

# Sempico Solutions SMS in Laravel

Supports Laravel 10, 11, 12 and 13. The service provider and the `Sempico` facade are registered automatically (package auto-discovery).

## Installation

```bash
composer require sempico/restapi-sms-php
php artisan vendor:publish --tag=sempico-config   # optional, creates config/sempico.php
```

`.env`:

```dotenv
SEMPICO_TOKEN=your-api-token
SEMPICO_SENDER_ID=MyBrand
SEMPICO_WEBHOOK_KEY=security-key-from-dashboard
# SEMPICO_TIMEOUT=30
# SEMPICO_VERIFY_SSL=true
```

## Facade

```php
use Sempico\RestApi\Laravel\Facades\Sempico;

Sempico::sendSms('380501234567', 'MyBrand', 'Your code: 4821');
Sempico::me();
Sempico::stat(['date_range' => '2026-11-01 00:00:00 - 2026-11-30 23:59:59']);
```

## Dependency injection

```php
use Sempico\RestApi\Sempico;
use Sempico\RestApi\ApiException;

class OrderController extends Controller
{
    public function confirm(Order $order, Sempico $sempico)
    {
        try {
            $sempico->sendSms($order->phone, config('sempico.sender_id'), "Order #{$order->id} is confirmed");
        } catch (ApiException $e) {
            report($e);
        }

        return back();
    }
}
```

## Notification channel

```php
use Illuminate\Notifications\Notification;
use Sempico\RestApi\Laravel\SempicoMessage;

class OrderShipped extends Notification
{
    public function __construct(private Order $order) {}

    public function via(object $notifiable): array
    {
        return ['sempico'];
    }

    public function toSempico(object $notifiable): SempicoMessage|string
    {
        return (new SempicoMessage())
            ->text("Your order #{$this->order->id} has been shipped")
            ->from('MyBrand') // optional, SEMPICO_SENDER_ID by default
            ->options(['callback_url' => route('sempico.dlr')]);
    }
}
```

The phone is taken from `routeNotificationForSempico()` or from the `phone` attribute:

```php
class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForSempico(): ?string
    {
        return $this->phone_number;
    }
}

$user->notify(new OrderShipped($order));

// without a model
Notification::route('sempico', '380501234567')->notify(new OrderShipped($order));
```

Make the notification `ShouldQueue` to send SMS from the queue: the `/send` requests must be synchronous, so a single worker for this queue is recommended.

## DLR webhook

`routes/api.php`:

```php
use Illuminate\Http\Request;
use Sempico\RestApi\DeliveryReport;

Route::post('/sempico/dlr', function (Request $request) {
    abort_unless(DeliveryReport::isValidKey($request->header('API_KEY'), config('sempico.webhook_key')), 403);

    $reports = DeliveryReport::parse($request->getContent());
    foreach ($reports as $report) {
        SmsLog::where('id_state', $report['id_state'])->update(['state' => $report['state']]);
    }

    return response(DeliveryReport::confirm($reports), 200, ['Content-Type' => 'application/json']);
})->name('sempico.dlr');
```

## Testing

Mock the facade in your tests:

```php
Sempico::shouldReceive('sendSms')->once()->andReturn(['380501234567' => [['id_state' => 1]]]);
```
