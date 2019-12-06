<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Model\CartItem;

class CartManager
{
    const SESSION_KEY = 'statamic.shopify.cart';

    const STRATEGY_MERGE = 'merge';
    const STRATEGY_REPLACE = 'replace';

    /**
     * Get all items in the cart.
     *
     * @return CartItem[]
     */
    public function get(): array
    {
        $items = session(self::SESSION_KEY, []);

        return collect($items)->map(function ($quantity, $variationId) {
            return new CartItem((int)$variationId, (int)$quantity);
        })->all();
    }

    /**
     * Store the given cart item in the cart.
     */
    public function store(CartItem $cartItem, $strategy = self::STRATEGY_MERGE): CartManager
    {
        if (!in_array($strategy, [self::STRATEGY_MERGE, self::STRATEGY_REPLACE])) {
            throw new \UnexpectedValueException(sprintf('%s is not a valid strategy', $strategy));
        }

        $items = session(self::SESSION_KEY, []);
        $newQuantity = $cartItem->getQuantity();

        if ($strategy === self::STRATEGY_MERGE) {
            // If we already have the given variation, combine the quantity.
            if (isset($items[$cartItem->getVariationId()])) {
                $newQuantity = (int)$items[$cartItem->getVariationId()] + $cartItem->getQuantity();
            }
        }

        if ($newQuantity > 0) {
            $items[$cartItem->getVariationId()] = $newQuantity;
        } else {
            // A quantity less than 1 does not make sense, we remove the item from the cart.
            unset($items[$cartItem->getVariationId()]);
        }

        session([self::SESSION_KEY => $items]);

        return $this;
    }

    /**
     * Remove the variation with the given item from the cart.
     */
    public function remove(int $variationId): CartManager
    {
        $items = session(self::SESSION_KEY, []);

        if (isset($items[$variationId])) {
            unset($items[$variationId]);
            session([self::SESSION_KEY => $items]);
        }

        return $this;
    }

    /**
     * Empty the cart.
     */
    public function clear(): CartManager
    {
        session()->forget(self::SESSION_KEY);

        return $this;
    }
}
