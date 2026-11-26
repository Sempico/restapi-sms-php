<?php

declare(strict_types=1);

namespace Sempico\RestApi\Laravel;

class SempicoMessage
{
    public function __construct(
        public string $text = '',
        public string $senderId = '',
        public array $options = []
    ) {
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function from(string $senderId): static
    {
        $this->senderId = $senderId;

        return $this;
    }

    /**
     * Extra /send params: type, beginDate, beginTime, lifetime, callback_url, delivery, campaign, shortener.
     */
    public function options(array $options): static
    {
        $this->options = $options + $this->options;

        return $this;
    }
}
