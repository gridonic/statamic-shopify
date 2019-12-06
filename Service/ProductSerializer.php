<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Event\SerializeProductEvent;
use Statamic\Extend\Extensible;

class ProductSerializer
{
    use Extensible;

    /**
     * Serialize the given product.
     */
    public function serialize($product): array
    {
        $serialized = json_decode(json_encode($product), true);

        $event = new SerializeProductEvent($product, $serialized);
        $this->emitEvent('serializeProduct', $event);

        return $event->getData();
    }
}
