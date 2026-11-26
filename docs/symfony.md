<p align="center"><a href="https://sempico.solutions"><img src="https://sempico.solutions/wp-content/uploads/2025/09/Logo.png" alt="Sempico Solutions" height="60"></a></p>

# Sempico Solutions SMS in Symfony

Symfony 6 and 7.

```bash
composer require sempico/restapi-sms-php
```

`.env.local`:

```dotenv
SEMPICO_TOKEN=your-api-token
SEMPICO_WEBHOOK_KEY=security-key-from-dashboard
```

`config/services.yaml`:

```yaml
services:
    Sempico\RestApi\ApiClient:
        arguments:
            $token: '%env(SEMPICO_TOKEN)%'
            $options:
                timeout: 30

    Sempico\RestApi\Sempico:
        arguments: ['@Sempico\RestApi\ApiClient']
```

## Usage (autowiring)

```php
use Sempico\RestApi\Sempico;

final class SmsNotifier
{
    public function __construct(private Sempico $sempico) {}

    public function sendCode(string $phone, string $code): void
    {
        $this->sempico->sendSms($phone, 'MyBrand', "Your code: $code");
    }
}
```

With Symfony Messenger, dispatch a message and call `SmsNotifier` in its handler, so SMS are sent from a worker.

## DLR webhook

```php
use Sempico\RestApi\DeliveryReport;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;

final class SempicoDlrController
{
    #[Route('/sempico/dlr', methods: ['POST'])]
    public function __invoke(Request $request, #[Autowire('%env(SEMPICO_WEBHOOK_KEY)%')] string $key): Response
    {
        if (!DeliveryReport::isValidKey($request->headers->get('API_KEY'), $key)) {
            return new Response('', 403);
        }

        $reports = DeliveryReport::parse($request->getContent());
        // ... save the states

        return JsonResponse::fromJsonString(DeliveryReport::confirm($reports));
    }
}
```
