<?php

namespace App\Service\WooCommerce;

use App\Service\WooCommerce\Exception\InvalidArgumentException;
use App\Service\WooCommerce\WebhookHandler\WebhookHandlerInterface;

class WebhookHandlerFactory
{
    private $handlers;

    public function __construct(WebhookHandlerInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $topic): WebhookHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($topic)) {
                return $handler;
            }
        }

        throw new InvalidArgumentException(sprintf('WebhookHandler not found for topic "%s"', $topic));
    }
}
