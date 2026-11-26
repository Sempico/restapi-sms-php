<?php

declare(strict_types=1);

namespace Sempico\RestApi\Laravel;

use Illuminate\Notifications\Notification;
use Sempico\RestApi\Sempico;

/**
 * Laravel notification channel "sempico".
 *
 * The notification must have toSempico($notifiable) returning a string (text)
 * or a SempicoMessage. The phone is taken from routeNotificationForSempico()
 * or the `phone` attribute of the notifiable.
 */
class SempicoChannel
{
    public function __construct(private Sempico $sempico, private string $defaultSender = '')
    {
    }

    public function send(object $notifiable, Notification $notification): mixed
    {
        $phone = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sempico', $notification)
            : null;
        $phone ??= $notifiable->phone ?? null;

        if (!$phone) {
            return null;
        }

        if (!method_exists($notification, 'toSempico')) {
            throw new \LogicException(sprintf('%s must have a toSempico($notifiable) method to be sent via "sempico".', $notification::class));
        }

        /** @var SempicoMessage|string $message */
        $message = $notification->toSempico($notifiable);
        if (is_string($message)) {
            $message = new SempicoMessage($message);
        }

        return $this->sempico->sendSms(
            $phone,
            $message->senderId ?: $this->defaultSender,
            $message->text,
            $message->options
        );
    }
}
