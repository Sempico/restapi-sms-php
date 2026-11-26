<p align="center"><a href="https://sempico.solutions"><img src="https://sempico.solutions/wp-content/uploads/2025/09/Logo.png" alt="Sempico Solutions" height="60"></a></p>

# Sempico Solutions SMS in Yii2

## Installation

```bash
composer require sempico/restapi-sms-php
```

`config/web.php` and `config/console.php`:

```php
'components' => [
    'sempico' => [
        'class'      => \Sempico\RestApi\Yii2\SempicoComponent::class,
        'token'      => getenv('SEMPICO_TOKEN'),
        'senderId'   => 'MyBrand',     // default Sender ID
        'webhookKey' => getenv('SEMPICO_WEBHOOK_KEY'),
        // 'timeout'    => 30,
        // 'verifySsl'  => true,
    ],
],
```

For IDE autocompletion add to your `Yii` class stub: `@property \Sempico\RestApi\Yii2\SempicoComponent $sempico`.

## Usage

```php
use Sempico\RestApi\ApiException;

try {
    // default senderId from the config
    Yii::$app->sempico->sendSms('380501234567', 'Your code: 4821');

    // any method of Sempico\RestApi\Sempico is available
    $me   = Yii::$app->sempico->me();
    $stat = Yii::$app->sempico->stat(['groups_choose' => ['day']]);
    Yii::$app->sempico->blackListAdd(['380501234567']);
} catch (ApiException $e) {
    Yii::error($e->getMessage(), 'sempico');
}
```

## Console command (sending from cron / queue)

```php
namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class SmsController extends Controller
{
    // php yii sms/send 380501234567 "Hello"
    public function actionSend(string $phone, string $text): int
    {
        $result = Yii::$app->sempico->sendSms($phone, $text);
        $this->stdout(json_encode($result) . PHP_EOL);

        return ExitCode::OK;
    }
}
```

With `yii2-queue`:

```php
class SendSmsJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public string $phone;
    public string $text;

    public function execute($queue): void
    {
        Yii::$app->sempico->sendSms($this->phone, $this->text);
    }
}

Yii::$app->queue->push(new SendSmsJob(['phone' => '380501234567', 'text' => 'Hello']));
```

## DLR webhook

```php
namespace app\controllers;

use Sempico\RestApi\DeliveryReport;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use Yii;

class SempicoController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionDlr(): Response
    {
        $request = Yii::$app->request;
        if (!DeliveryReport::isValidKey($request->headers->get('API_KEY'), Yii::$app->sempico->webhookKey)) {
            throw new ForbiddenHttpException();
        }

        $reports = DeliveryReport::parse($request->getRawBody());
        foreach ($reports as $report) {
            SmsLog::updateAll(['state' => $report['state']], ['id_state' => $report['id_state']]);
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/json');
        $response->content = DeliveryReport::confirm($reports);

        return $response;
    }
}
```

Use `https://your-site.com/sempico/dlr` as `callback_url`.
