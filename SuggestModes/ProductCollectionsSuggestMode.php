<?php

namespace Statamic\Addons\Shopify\SuggestModes;

use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Addons\Suggest\Modes\AbstractMode;

/**
 * A suggest mode returning product collections from Shopify.
 */
class ProductCollectionsSuggestMode extends AbstractMode
{
    /**
     * @var \Statamic\Addons\Shopify\Service\ShopifyRepository
     */
    private $shopifyRepository;

    public function __construct(ShopifyRepository $shopifyRepository)
    {
        $this->shopifyRepository = $shopifyRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function suggestions()
    {
        $collections = $this->shopifyRepository->getProductCollections();

        return collect($collections)->map(function ($collection) {
            return [
                'value' => $collection->collection_id,
                'text' => $collection->title,
            ];
        })->values()->all();
    }
}
