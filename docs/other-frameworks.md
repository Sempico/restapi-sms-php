<p align="center"><a href="https://sempico.solutions"><img src="https://sempico.solutions/wp-content/uploads/2025/09/Logo.png" alt="Sempico Solutions" height="60"></a></p>

# Sempico Solutions SMS in other frameworks

- [Plain PHP](#plain-php)
- [CodeIgniter 4](#codeigniter-4)
- [Slim 4](#slim-4)
- [WordPress / WooCommerce](#wordpress--woocommerce)

## Plain PHP

```php
require __DIR__ . '/vendor/autoload.php';

$sempico = new Sempico\RestApi\Sempico(getenv('SEMPICO_TOKEN'));
$sempico->sendSms('380501234567', 'MyBrand', 'Hello!');
```

## CodeIgniter 4

`app/Config/Services.php`:

```php
use Sempico\RestApi\Sempico;

public static function sempico(bool $getShared = true): Sempico
{
    if ($getShared) {
        return static::getSharedInstance('sempico');
    }

    return new Sempico(env('SEMPICO_TOKEN'));
}
```

Controller:

```php
service('sempico')->sendSms('380501234567', 'MyBrand', 'Your code: 4821');
```

DLR webhook (`$routes->post('sempico/dlr', 'Sempico::dlr');`, exclude it from CSRF):

```php
use Sempico\RestApi\DeliveryReport;

class Sempico extends BaseController
{
    public function dlr()
    {
        if (!DeliveryReport::isValidKey($this->request->getHeaderLine('API_KEY'), env('SEMPICO_WEBHOOK_KEY'))) {
            return $this->response->setStatusCode(403);
        }

        $reports = DeliveryReport::parse($this->request->getBody());
        // ... save the states

        return $this->response->setContentType('application/json')->setBody(DeliveryReport::confirm($reports));
    }
}
```

## Slim 4

```php
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Sempico\RestApi\{DeliveryReport, Sempico};

$container->set(Sempico::class, fn () => new Sempico($_ENV['SEMPICO_TOKEN']));

$app->post('/sempico/dlr', function (ServerRequestInterface $request, ResponseInterface $response) {
    if (!DeliveryReport::isValidKey($request->getHeaderLine('API_KEY'), $_ENV['SEMPICO_WEBHOOK_KEY'])) {
        return $response->withStatus(403);
    }

    $reports = DeliveryReport::parse((string) $request->getBody());
    $response->getBody()->write(DeliveryReport::confirm($reports));

    return $response->withHeader('Content-Type', 'application/json');
});
```

## WordPress / WooCommerce

Install the library with Composer in your plugin and send an SMS when an order is completed:

```php
require __DIR__ . '/vendor/autoload.php';

use Sempico\RestApi\{ApiException, Sempico};

add_action('woocommerce_order_status_completed', function (int $orderId) {
    $order = wc_get_order($orderId);
    $sempico = new Sempico(get_option('sempico_token'));

    try {
        $sempico->sendSms($order->get_billing_phone(), 'MyShop', sprintf('Order #%d is completed. Thank you!', $orderId));
    } catch (ApiException $e) {
        error_log('Sempico: ' . $e->getMessage());
    }
});
```
