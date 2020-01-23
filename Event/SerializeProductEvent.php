<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Addons\Shopify\Model\SerializationContext;
use Statamic\Events\Event;

class SerializeProductEvent extends Event
{
    /**
     * @var array
     */
    private $data;

    private $product;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    public function __construct($product, array $data, SerializationContext $context)
    {
        $this->data = $data;
        $this->product = $product;
        $this->serializationContext = $context;
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

    public function getSerializationContext(): SerializationContext
    {
        return $this->serializationContext;
    }
}
