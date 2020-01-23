<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Event\SerializeProductEvent;
use Statamic\Addons\Shopify\Model\SerializationContext;
use Statamic\Extend\Extensible;

class ProductSerializer
{
    use Extensible;

    /**
     * Serialize the given product.
     */
    public function serialize($product, SerializationContext $context): array
    {
        $serialized = json_decode(json_encode($product), true);

        $event = new SerializeProductEvent($product, $serialized, $context);
        $this->emitEvent('serializeProduct', $event);

        return $event->getData();
    }
}
