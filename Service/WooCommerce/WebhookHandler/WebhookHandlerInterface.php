<?php

namespace App\Service\WooCommerce\WebhookHandler;

use App\Entity\SalesChannel;
use App\Service\WooCommerce\Api\GenericResource;

interface WebhookHandlerInterface
{
    public function supports(string $topic): bool;

    public function handle(SalesChannel $salesChannel, GenericResource $resource): void;
}
