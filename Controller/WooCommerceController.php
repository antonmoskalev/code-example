<?php

namespace App\Controller;

use App\Entity\SalesChannel;
use App\Exception\Exception;
use App\Service\WooCommerce\Api\GenericResource;
use App\Service\WooCommerce\Credentials;
use App\Service\WooCommerce\Form\CallbackFormType;
use App\Service\WooCommerce\Weebhook\WebhookManager;
use App\Service\WooCommerce\WebhookHandlerFactory;
use App\Service\WooCommerce\Weebhook\WebhookValidator;
use App\Service\WooCommerce\WooCommerceAppAuth;
use App\Service\WooCommerce\WooCommerceStoreManager;
use App\Traits\SentrySymfonyClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Sentry\SentryBundle\SentrySymfonyClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("woocommerce", name="woocommerce_")
 */
class WooCommerceController extends Controller
{
    use SentrySymfonyClientTrait;

    public function __construct(SentrySymfonyClient $sentrySymfonyClient = null)
    {
        $this->sentrySymfonyClient = $sentrySymfonyClient;
    }

    /**
     * @Route("/webhook/{id}", name="webhook", requirements={"id"="\d+"})
     */
    public function webhook(
        SalesChannel $salesChannel,
        Request $request,
        WebhookHandlerFactory $webhookHandlerFactory,
        WebhookValidator $webhookValidator
    ) {
        $topic = $request->headers->get('X-WC-Webhook-Topic');
        $payload = json_decode($request->getContent(), true);

        $this->get('monolog.logger.woocommerce_webhooks')->info('Woocommerce Webhook Received', [
            'topic' => $topic,
            'store' => $salesChannel->getName(),
            'resource' => $payload,
        ]);

        try {
            $webhookValidator->validateRequest($request, $salesChannel);
            $webhookHandlerFactory->get($topic)->handle($salesChannel, GenericResource::create($payload));
        } catch (\Exception $e) {
            $this->sentryReport($e);
        }

        return new Response('ok');
    }
}
