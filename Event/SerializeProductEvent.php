<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Events\Event;

class SerializeProductEvent extends Event
{
    /**
     * @var array
     */
    private $data;

    private $product;

    public function __construct($product, array $data)
    {
        $this->data = $data;
        $this->product = $product;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getProduct()
    {
        return $this->product;
    }
}
