<?php

namespace App\Service\WooCommerce\WebhookHandler;

use App\Entity\SalesChannel;
use App\Entity\ShopProduct;
use App\Event\ShopProductEvent;
use App\Service\WooCommerce\Api\GenericResource;
use App\Service\WooCommerce\Api\WooCommerceApiFactory;
use App\Service\WooCommerce\ImportProductManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateProductWebhookHandler implements WebhookHandlerInterface
{
    private $em;

    private $apiFactory;

    private $importProductManager;

    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        WooCommerceApiFactory $apiFactory,
        ImportProductManager $importProductManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->apiFactory = $apiFactory;
        $this->importProductManager = $importProductManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function supports(string $topic): bool
    {
        return $topic === 'product.updated';
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

        $api = $this->apiFactory->getForStore($salesChannel->getName());

        $this->importProductManager->update($shopProduct, $product, $api);
        $this->eventDispatcher->dispatch(ShopProductEvent::UPDATING, new ShopProductEvent($shopProduct));

        if ($shopProduct->getVariants()->isEmpty()) {
            $this->em->remove($shopProduct);
        }

        $this->em->flush();
    }
}
