<?php

namespace Statamic\Addons\Shopify\Model;

class CartItem
{
    /**
     * @var int
     */
    private $variationId;

    /**
     * @var int
     */
    private $quantity;

    public function __construct(int $variationId, int $quantity)
    {
        $this->variationId = $variationId;
        $this->quantity = $quantity;
    }

    public function setVariationId(int $variationId): CartItem
    {
        $this->variationId = $variationId;

        return $this;
    }

    public function setQuantity(int $quantity): CartItem
    {
        $this->quantity = max($quantity, 0);

        return $this;
    }

    public function getVariationId(): int
    {
        return $this->variationId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
