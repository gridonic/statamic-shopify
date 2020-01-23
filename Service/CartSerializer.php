<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Model\CartItem;
use Statamic\Addons\Shopify\Model\SerializationContext;

class CartSerializer
{
    /**
     * @var \Statamic\Addons\Shopify\Service\ShopifyRepository
     */
    private $shopifyRepository;

    /**
     * @var \Statamic\Addons\Shopify\Service\ProductVariantSerializer
     */
    private $productVariantSerializer;

    public function __construct(ShopifyRepository $shopifyRepository, ProductVariantSerializer $productVariantSerializer)
    {
        $this->shopifyRepository = $shopifyRepository;
        $this->productVariantSerializer = $productVariantSerializer;
    }

    /**
     * Serialize the given cart items.
     */
    public function serializeCartItems(array $cartItems): array
    {
        return collect($cartItems)->map(function ($cartItem) {
            /** @var CartItem $cartItem */
            $variant = $this->shopifyRepository->getProductVariant($cartItem->getVariationId());

            if (!$variant) {
                return false;
            }

            $context = new SerializationContext(SerializationContext::CONTEXT_PRODUCT_VARIANT_CART);

            return [
                'quantity' => $cartItem->getQuantity(),
                'variant' => $this->productVariantSerializer->serialize($variant, $context),
            ];
        })->filter()->values()->all();
    }
}
