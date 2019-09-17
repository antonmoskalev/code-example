<?php

namespace App\Service\WooCommerce\WebhookHandler;

use App\Entity\SalesChannel;
use App\Entity\ShopProduct;
use App\Service\WooCommerce\Api\GenericResource;
use Doctrine\ORM\EntityManagerInterface;

class DeleteProductWebhookHandler implements WebhookHandlerInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function supports(string $topic): bool
    {
        return $topic === 'product.deleted';
    }

    public function handle(SalesChannel $salesChannel, GenericResource $product): void
    {
        $shopProduct = $this->em->getRepository('App:ShopProduct')->findOneBy([
            'foreignId' => $product->get('id'),
            'salesChannel' => $salesChannel,
            'statusImport' => ShopProduct::STATUS_IMPORTED,
        ]);

        if ($shopProduct === null) {
            return;
        }

        $this->em->remove($shopProduct);
        $this->em->flush();
    }
}
