<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Event\CheckoutCreateEvent;
use Statamic\Extend\Extensible;

/**
 * Manages the creation and updating of a Shopify checkout.
 */
class CheckoutManager
{
    use Extensible;

    const SESSION_KEY = 'statamic.shopify.checkout';

    /**
     * @var \Statamic\Addons\Shopify\Service\CheckoutClient
     */
    private $checkoutClient;

    public function __construct(CheckoutClient $checkoutClient)
    {
        $this->checkoutClient = $checkoutClient;
    }

    /**
     * Get the Shopify checkout ID.
     */
    public function getId(): ?string
    {
        $data = session(self::SESSION_KEY, []);

        return $data['id'] ?? null;
    }

    /**
     * Get the checkout URL from Shopify.
     */
    public function getUrl(): ?string
    {
        $data = session(self::SESSION_KEY, []);

        return $data['url'] ?? null;
    }

    /**
     * Create or update the Shopify checkout with the given cart items.
     */
    public function createOrUpdate(array $cartItems)
    {
        if (!$this->getId()) {
            $response = $this->checkoutClient->create($cartItems);
            $body = json_decode((string)$response->getBody());

            $checkoutId = $body->data->checkoutCreate->checkout->id;
            $checkoutUrl = $body->data->checkoutCreate->checkout->webUrl;

            $event = new CheckoutCreateEvent($checkoutId, $checkoutUrl, $cartItems);
            $this->emitEvent('checkoutCreate', $event);

            $sessionData = array_merge([
                'id' => $event->getId(),
                'url' => $event->getUrl(),
            ], $event->getData());

            session([self::SESSION_KEY => $sessionData]);
        } else {
            $this->checkoutClient->update($cartItems, $this->getId());
        }
    }

    /**
     * Clear any stored checkout data in the session.
     */
    public function clear(): CheckoutManager
    {
        session()->forget(self::SESSION_KEY);

        return $this;
    }
}
