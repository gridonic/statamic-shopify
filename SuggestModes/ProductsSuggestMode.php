<?php

namespace Statamic\Addons\Shopify\SuggestModes;

use Statamic\Addons\Shopify\Event\ProductsSuggestionsEvent;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Addons\Suggest\Modes\AbstractMode;
use Statamic\Extend\Extensible;
use Statamic\View\Antlers\Template;

/**
 * A suggest mode returning products from Shopify.
 */
class ProductsSuggestMode extends AbstractMode
{
    use Extensible;

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
        // Let other customize the returned products.
        $event = new ProductsSuggestionsEvent(['published_status' => 'published']);
        $this->emitEvent('productsSuggestions', $event);

        $products = $this->shopifyRepository->getProducts($event->getFilters());

        $label = $this->getConfig('suggest_mode_products_label', '{{ title }} ({{ product_type }})');

        return collect($products)->map(function ($product) use ($label) {
            return [
                'value' => $product->id,
                'text' => Template::parse($label, (array)$product),
            ];
        })->values()->all();
    }
}
