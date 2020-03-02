<?php

namespace Statamic\Addons\Shopify;

use Illuminate\Support\Collection;
use Statamic\Addons\Shopify\Event\ProductsTagEvent;
use Statamic\Addons\Shopify\Exception\ShopifyApiException;
use Statamic\Addons\Shopify\Model\PaginatedItems;
use Statamic\Addons\Shopify\Model\SerializationContext;
use Statamic\Addons\Shopify\Service\CartManager;
use Statamic\Addons\Shopify\Service\CartSerializer;
use Statamic\Addons\Shopify\Service\CheckoutManager;
use Statamic\Addons\Shopify\Service\ProductSerializer;
use Statamic\Addons\Shopify\Service\ProductVariantSerializer;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Exceptions\RedirectException;
use Statamic\Exceptions\UrlNotFoundException;
use Statamic\Extend\Extensible;
use Statamic\Extend\Tags;

/**
 * Provides Antlers tags to interact with the Shopify API.
 */
class ShopifyTags extends Tags
{
    use Extensible;

    /**
     * @var \Statamic\Addons\Shopify\Service\ShopifyRepository
     */
    private $shopifyRepository;

    /**
     * @var \Statamic\Addons\Shopify\Service\ProductSerializer
     */
    private $productSerializer;

    /**
     * @var \Statamic\Addons\Shopify\Service\CartManager
     */
    private $cartManager;

    /**
     * @var \Statamic\Addons\Shopify\Service\CheckoutManager
     */
    private $checkoutManager;

    /**
     * @var \Statamic\Addons\Shopify\Service\CartSerializer
     */
    private $cartSerializer;

    /**
     * @var \Statamic\Addons\Shopify\Service\ProductVariantSerializer
     */
    private $productVariantSerializer;

    public function __construct(
        ShopifyRepository $shopifyRepository,
        ProductSerializer $productSerializer,
        ProductVariantSerializer $productVariantSerializer,
        CartManager $cartManager,
        CheckoutManager $checkoutManager,
        CartSerializer $cartSerializer
    )
    {
        parent::__construct();

        $this->shopifyRepository = $shopifyRepository;
        $this->productSerializer = $productSerializer;
        $this->cartManager = $cartManager;
        $this->checkoutManager = $checkoutManager;
        $this->cartSerializer = $cartSerializer;
        $this->productVariantSerializer = $productVariantSerializer;
    }

    /**
     * The {{ shopify:products }} tag.
     *
     * Returns multiple products, optionally filtered by the provided filter string.
     * For a list of available filters, see: https://help.shopify.com/en/api/reference/products/product
     *
     * Parameters:
     *  - filter <string>: Key value pair of filters, separated with "&".
     *  - sort_by_collection <bool>: Optionally sort the products by the given collection_id in the filters.
     *  - paginated <bool>: True to enable pagination.
     *  - pagination_query_string <string>: The query string holding the page info, when using pagination.
     *  - as_json <bool>: Whether to return the data as JSON.
     *  - serialization_context_id <string>: An optional context id passed to the serializer when serializing product data.
     *
     * Example:
     *
     * {{ shopify:products filters="product_type=foo&vendor=bar&published_status=published&limit=150" }}
     */
    public function products()
    {
        $filters = $this->buildFilters();
        $shouldPaginate = $this->getParamBool('paginated', false);
        $paginationQueryString = $this->getParam('pagination_query_string', 'products_page');
        $asJson = $this->getParamBool('as_json', false);
        $sortByCollection = $this->getParamBool('sort_by_collection');
        $serializationContextId = $this->getParam('serialization_context_id', SerializationContext::CONTEXT_PRODUCT_LIST);

        if ($shouldPaginate) {
            $pageInfo = request()->get('products_page', null);
            try {
                $paginatedItems = $this->shopifyRepository->getProductsPaginated($filters, $pageInfo);
                $output = $this->getPaginatedOutput($paginatedItems, $paginationQueryString, 'products', $serializationContextId);
            } catch (ShopifyApiException $exception) {
                // If the page info query string is not valid, fail silently.
                if ($this->isInvalidPageInfo($exception)) {
                    $output = [];
                } else {
                    throw $exception;
                }
            }

            return $asJson ? json_encode($output) : $this->parse($output);
        }

        $products = collect($this->shopifyRepository->getProducts($filters));

        // Should we sort the productions by the provided Shopify collection in the filter params?
        if ($sortByCollection && isset($filters['collection_id'])) {
            $products = $this->sortProductsByCollectionId($products, $filters['collection_id']);
        }

        $serializedProducts = $this->serializeProducts($products->all(), $serializationContextId);

        if ($asJson) {
            return json_encode($serializedProducts);
        }

        return $this->parseLoop($serializedProducts);
    }

    /**
     * The {{ shopify:product_variants }} tag.
     *
     * Returns all product variants of a given product.
     * For a list of available filters, see: https://help.shopify.com/en/api/reference/products/product-variant
     *
     * Parameters:
     *  - product_id <int>: The id of the product.
     *  - filter <string>: Key value pair of filters, separated with "&".
     *  - as_json <bool>: Whether to return the data as JSON.
     *  - serialization_context_id <string>: An optional context id passed to the serializer when serializing product data.
     */
    public function productVariants()
    {
        $productId = $this->getParamInt('product_id');
        $variants = $this->shopifyRepository->getProductVariants($productId, $this->buildFilters());
        $serializationContextId = $this->getParam('serialization_context_id', SerializationContext::CONTEXT_PRODUCT_VARIANT_LIST);

        $context = new SerializationContext($serializationContextId);

        $serialized = collect($variants)->map(function ($variant) use ($context) {
            return $this->productVariantSerializer->serialize($variant, $context);
        })->values()->all();

        if ($this->getParamBool('as_json')) {
            return json_encode($serialized);
        }

        return $this->parseLoop($serialized);
    }

    /**
     * The {{ shopify:product }} tag.
     *
     * Returns serialized product data of a product either by id or handle.
     *
     * Parameters:
     *  - id <int>: The id of the product.
     *  - handle <string>: The handle of the product.
     *  - as_json <bool>: Whether to return the data as JSON.
     *  - throw_404 <bool>: Whether to throw a 404 exception if the product is not found.
     *  - serialization_context_id <string>: An optional context id passed to the serializer when serializing product data.
     */
    public function product()
    {
        if ($this->getParamInt('id')) {
            $id = $this->getParamInt('id');
            $product = $this->blink->get("shopify.product.id.{$id}") ?: $this->shopifyRepository->getProduct($id);
        } elseif ($this->getParam('handle')) {
            $handle = $this->getParam('handle');
            $product = $this->blink->get("shopify.product.handle.{$handle}") ?: $this->shopifyRepository->getProductByHandle($handle);
        } else {
            throw new \LogicException('Missing product id or handle');
        }

        if ($product === null) {
            if ($this->getParamBool('throw_404', false)) {
                throw new UrlNotFoundException();
            }

            return null;
        }

        // Store product data in blink cache (cached per request).
        $this->blink->put("shopify.product.id.{$product->id}", $product);
        $this->blink->put("shopify.product.handle.{$product->handle}", $product);

        // Get the serialized product data from cache blink cache. If not already cached, store it.
        $serialized = $this->blink->get("shopify.product.{$product->id}.serialized");
        if (!$serialized) {
            $serializationContextId = $this->getParam('serialization_context_id', SerializationContext::CONTEXT_PRODUCT_DETAIL);
            $context = new SerializationContext($serializationContextId);
            $serialized = $this->productSerializer->serialize($product, $context);
            $this->blink->put("shopify.product.{$product->id}.serialized", $serialized);
        }

        if ($this->getParamBool('as_json')) {
            return json_encode($serialized);
        }

        return $serialized;
    }

    /**
     * The {{ shopify:product_variant }} tag.
     *
     * Returns serialized product variant data of a given product variant.
     *
     * Parameters:
     *  - id <int>: The ID of the product variant.
     *  - as_json <bool>: Whether to return the data as JSON.
     *  - throw_404 <bool>: Whether to throw a 404 exception if the variant is not found.
     *  - serialization_context_id <string>: An optional context id passed to the serializer when serializing product data.
     */
    public function productVariant()
    {
        $variant = $this->shopifyRepository->getProductVariant($this->getParamInt('id'));

        if ($variant === null) {
            if ($this->getParamBool('throw_404', false)) {
                throw new UrlNotFoundException();
            }

            return null;
        }

        $serializationContextId = $this->getParam('serialization_context_id', SerializationContext::CONTEXT_PRODUCT_VARIANT_DETAIL);
        $context = new SerializationContext($serializationContextId);
        $serialized = $this->productVariantSerializer->serialize($variant, $context);

        if ($this->getParamBool('as_json')) {
            return json_encode($serialized);
        }

        return $serialized;
    }

    /**
     * The {{ shopify:products_count }} tag.
     *
     * Returns the number of products, optionally filtered by a filter string.
     *
     * Parameters:
     *  - filter <string>: Key value pair of filters, separated with "&".
     */
    public function productsCount()
    {
        return $this->shopifyRepository->getProductsCount($this->buildFilters());
    }

    /**
     * The {{ shopify:product_variants_count }} tag.
     *
     * Returns the number of product variants of the given product.
     *
     * Parameters:
     *  - product_id <int>: The id of the product.
     */
    public function productVariantsCount()
    {
        $productId = $this->getParamInt('product_id');

        return $this->shopifyRepository->getProductVariantsCount($productId);
    }

    /**
     * The {{ shopify:cart_items }} tag.
     *
     * Returns all product variants with quantities in the shopping cart.
     */
    public function cartItems()
    {
        $cartItems = $this->cartManager->get();

        return $this->parseLoop(
            $this->cartSerializer->serializeCartItems($cartItems)
        );
    }

    /**
     * The {{ shopify:cart_count }} tag.
     *
     * Returns the number of items in the cart.
     */
    public function cartCount()
    {
        $perVariation = $this->getParamBool('per_variation', false);
        $cartItems = collect($this->cartManager->get());

        if ($perVariation) {
            return $cartItems->count();
        }

        return $cartItems->map(function ($cartItem) {
            /** @var $cartItem CartItem */
            return $cartItem->getQuantity();
        })->sum();
    }

    /**
     * The {{ shopify:cart_clear }} tag.
     *
     * Clears the cart of the current user.
     */
    public function cartClear()
    {
        $this->cartManager->clear();
    }

    /**
     * The {{ shopify:checkout_url }} tag.
     *
     * Returns the URL to the Shopify checkout.
     */
    public function checkoutUrl()
    {
        return $this->checkoutManager->getUrl();
    }

    /**
     * The {{ shopify:checkout_update }} tag.
     *
     * Creates or updates the Shopify checkout with the current cart's items.
     */
    public function checkoutUpdate()
    {
        $this->checkoutManager->createOrUpdate($this->cartManager->get());
    }

    /**
     * The {{ shopify:checkout_redirect }} tag.
     *
     * Performs a redirect to the Shopify checkout.
     */
    public function checkoutRedirect()
    {
        if (!$this->checkoutManager->getUrl()) {
            return;
        }

        $exception = (new RedirectException())
            ->setUrl($this->checkoutManager->getUrl());

        throw $exception;
    }

    /**
     * The {{ shopify:checkout_clear }} tag.
     *
     * Clears the checkout of the current user.
     */
    public function checkoutClear()
    {
        $this->checkoutManager->clear();
    }

    private function buildFilters(): array
    {
        if (!$this->getParam('filters')) {
            return [];
        }

        $filterParts = explode('&', $this->getParam('filters'));

        collect($filterParts)->each(function ($filterPart) use (&$filters) {
            [$name, $value] = explode('=', $filterPart);
            $filters[$name] = $value;
        });

        return $filters;
    }

    private function serializeProducts(array $products, string $serializationContextId): array
    {
        $context = new SerializationContext($serializationContextId);

        return collect($products)->map(function ($product) use ($context) {
            return $this->productSerializer->serialize($product, $context);
        })->values()->all();
    }

    private function getPaginatedOutput(PaginatedItems $paginatedItems, string $paginationQueryString, string $itemsKey, string $serializationContextId): array
    {
        $products = $paginatedItems->getItems();
        $serializedProducts = $this->serializeProducts($products, $serializationContextId);
        $queryStrings = request()->query();

        $previousLink = $paginatedItems->getPreviousLink();
        if ($previousLink) {
            $previousLink = '?' . http_build_query(array_merge($queryStrings, [$paginationQueryString => $previousLink]));
        }

        $nextLink = $paginatedItems->getNextLink();
        if ($nextLink) {
            $nextLink = '?' . http_build_query(array_merge($queryStrings, [$paginationQueryString => $nextLink]));
        }

        return [
            $itemsKey => $serializedProducts,
            'paginate' => [
                'previous_url' => $previousLink,
                'next_url' => $nextLink,
            ],
        ];
    }

    private function isInvalidPageInfo(ShopifyApiException $exception): bool
    {
        /** @var \GuzzleHttp\Exception\RequestException $clientException */
        $clientException = $exception->getPrevious();
        $body = json_decode((string)$clientException->getResponse()->getBody());

        return isset($body->errors->page_info);
    }

    private function sortProductsByCollectionId(Collection $products, $collectionId): Collection
    {
        $productCollects = collect($this->shopifyRepository->getProductsOfCollection($collectionId));

        $productsSorted = collect();

        $productCollects->each(function ($productCollect) use ($productsSorted, $products) {
            $product = $products->filter(function ($product) use ($productCollect) {
                return $product->id === $productCollect->id;
            })->first();
            $productsSorted->push($product);
        });

        return $productsSorted->filter()->values();
    }
}
