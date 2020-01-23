<?php

namespace Statamic\Addons\Shopify\Service;

use Statamic\Addons\Shopify\Event\SerializeProductVariantEvent;
use Statamic\Addons\Shopify\Model\SerializationContext;
use Statamic\Extend\Extensible;

class ProductVariantSerializer
{
    use Extensible;

    /**
     * Serialize the given product variant.
     */
    public function serialize($variant, SerializationContext $context): array
    {
        $serialized = json_decode(json_encode($variant), true);

        $event = new SerializeProductVariantEvent($variant, $serialized, $context);
        $this->emitEvent('serializeProductVariant', $event);

        return $event->getData();
    }
}
