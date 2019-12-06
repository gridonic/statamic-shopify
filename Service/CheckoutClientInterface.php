<?php

namespace Statamic\Addons\Shopify\Service;

use Psr\Http\Message\ResponseInterface;

interface CheckoutClientInterface
{
    /**
     * Create a new checkout holding the given cart items as line items.
     *
     * @param array $cartItems
     */
    public function create(array $cartItems): ResponseInterface;

    /**
     * Update the line items of the given checkout id with the cart items.
     *
     * @param array $cartItems
     * @param string $checkoutId
     */
    public function update(array $cartItems, string $checkoutId): ResponseInterface;
}
