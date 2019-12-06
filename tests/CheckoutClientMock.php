<?php

namespace Statamic\Addons\Shopify\tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Statamic\Addons\Shopify\Service\CheckoutClientInterface;

class CheckoutClientMock implements CheckoutClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $cartItems): ResponseInterface
    {
        $body = json_encode([
            'data' => [
                'checkoutCreate' => [
                    'checkout' => [
                        'id' => 'checkoutId',
                        'webUrl' => 'checkoutUrl',
                    ],
                ],
            ],
        ]);

        return new Response(200, [], $body);
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $cartItems, string $checkoutId): ResponseInterface
    {
    }
}
