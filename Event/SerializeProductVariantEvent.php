<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Events\Event;

class SerializeProductVariantEvent extends Event
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var
     */
    private $productVariant;

    public function __construct($productVariant, array $data)
    {
        $this->data = $data;
        $this->productVariant = $productVariant;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getProductVariant()
    {
        return $this->productVariant;
    }
}
