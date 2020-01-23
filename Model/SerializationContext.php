<?php

namespace Statamic\Addons\Shopify\Model;

class SerializationContext
{
    const CONTEXT_PRODUCT_DETAIL = 'shopify.product_detail';
    const CONTEXT_PRODUCT_LIST = 'shopify.product_list';
    const CONTEXT_PRODUCT_VARIANT_DETAIL = 'shopify.product_variant_detail';
    const CONTEXT_PRODUCT_VARIANT_LIST = 'shopify.product_variant_list';
    const CONTEXT_PRODUCT_VARIANT_CART = 'shopify.product_variant_cart';

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): SerializationContext
    {
        $this->data = $data;
        return $this;
    }
}
