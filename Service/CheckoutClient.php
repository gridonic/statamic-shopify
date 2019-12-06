<?php

namespace Statamic\Addons\Shopify\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use OhMyBrew\BasicShopifyAPI;
use Psr\Http\Message\ResponseInterface;
use Statamic\Addons\Shopify\Exception\ShopifyApiException;
use Statamic\Addons\Shopify\Model\CartItem;

/**
 * A client to call Shopify's Storefront checkout API via GraphQL.
 *
 * @see https://help.shopify.com/en/api/storefront-api
 */
class CheckoutClient implements CheckoutClientInterface
{
    /**
     * @var \OhMyBrew\BasicShopifyAPI
     */
    private $client;

    /**
     * @var string
     */
    private $storefrontAccessToken;

    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    public function __construct(BasicShopifyAPI $client, string $storefrontAccessToken)
    {
        $this->client = $client;
        $this->storefrontAccessToken = $storefrontAccessToken;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $cartItems): ResponseInterface
    {
        $requestBody = $this->buildCreateCheckoutQuery($cartItems);

        return $this->sendRequest($requestBody);
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $cartItems, string $checkoutId): ResponseInterface
    {
        $requestBody = $this->buildUpdateCheckoutQuery($cartItems, $checkoutId);

        return $this->sendRequest($requestBody);
    }

    private function sendRequest($requestBody): ResponseInterface
    {
        $request = new Request('POST', $this->getGraphQLUrl(), [], $requestBody);

        try {
            $response = $this->getHttpClient()->send($request);
        } catch (RequestException $exception) {
            throw new ShopifyApiException($exception->getMessage(), 0, $exception);
        }

        $body = json_decode((string) $response->getBody());
        if (isset($body->errors)) {
            throw new ShopifyApiException($body->errors[0]->message);
        }

        return $response;
    }

    private function getHttpClient()
    {
        if ($this->httpClient !== null) {
            return $this->httpClient;
        }

        $this->httpClient = new Client([
            'headers'  => [
                'Content-Type' => 'application/graphql',
                'Accept' => 'application/json',
                'X-Shopify-Storefront-Access-Token' => $this->storefrontAccessToken,
            ],
        ]);

        return $this->httpClient;
    }

    private function getGraphQLUrl(): string
    {
        return 'https://' . $this->client->getShop() . '/api/2019-07/graphql';
    }

    private function buildCreateCheckoutQuery(array $cartItems): string
    {
        $mutationQuery = 'mutation {
  checkoutCreate(input: {
    lineItems: %s
  }) {
    checkout {
       id
       webUrl
    }
  }
}';

        $lineItems = $this->buildGraphQLLineItems($cartItems);

        return sprintf($mutationQuery, $lineItems);
    }

    private function buildUpdateCheckoutQuery(array $cartItems, string $checkoutId): string
    {
        $mutationQuery = 'mutation {
  checkoutLineItemsReplace(lineItems: %s, checkoutId: "%s",
  ) {
    checkout {
       id
       webUrl
    }
  }
}';
        $lineItems = $this->buildGraphQLLineItems($cartItems);

        return sprintf($mutationQuery, $lineItems, $checkoutId);
    }

    private function buildGraphQLLineItems(array $cartItems): string
    {
        $lineItems = collect($cartItems)->map(function ($cartItem) {
            /** @var CartItem $cartItem */
            return [
                'variantId' => base64_encode(sprintf('gid://shopify/ProductVariant/%s', $cartItem->getVariationId())),
                'quantity' => $cartItem->getQuantity(),
            ];
        })->values()->all();

        $json = json_encode($lineItems);

        return str_replace(['"variantId"', '"quantity"'], ['variantId', 'quantity'], $json);
    }
}
