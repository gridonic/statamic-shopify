<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Addons\Shopify\Model\SerializationContext;
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

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    public function __construct($productVariant, array $data, SerializationContext $context)
    {
        $this->data = $data;
        $this->productVariant = $productVariant;
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

    public function getProductVariant()
    {
        return $this->productVariant;
    }

    public function getSerializationContext(): SerializationContext
    {
        return $this->serializationContext;
    }
}
