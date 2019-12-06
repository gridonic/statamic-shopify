<?php

namespace Statamic\Addons\Shopify\Service;

use OhMyBrew\BasicShopifyAPI;
use Statamic\Addons\Shopify\Exception\ShopifyApiException;
use Statamic\Addons\Shopify\Model\PaginatedItems;
use stdClass;

class ShopifyRepository
{
    const LIMIT_DEFAULT = 50;
    const LIMIT_MAX = 250;

    /**
     * @var \OhMyBrew\BasicShopifyAPI
     */
    private $client;

    public function __construct(BasicShopifyAPI $client)
    {
        $this->client = $client;
    }

    public function getProducts(array $params = []): array
    {
        return $this->getAllWithMultipleRequests('/admin/products.json', 'products', $params);
    }

    public function getProductsPaginated(array $params = [], string $pageInfo = null): PaginatedItems
    {
        return $this->getPaginatedItems('/admin/products.json', 'products', $params, $pageInfo);
    }

    public function getProduct(int $id): ?stdClass
    {
        $result = $this->client->rest('GET', sprintf('/admin/products/%s.json', $id));

        return $this->getSingle($result, 'product');
    }

    public function getProductByHandle(string $handle): ?stdClass
    {
        $products = collect($this->getProducts(['handle' => $handle]));

        return $products->count() ? $products->first() : null;
    }

    public function getProductVariant(int $id): ?stdClass
    {
        $result = $this->client->rest('GET', sprintf('/admin/variants/%s.json', $id));

        return $this->getSingle($result, 'variant');
    }

    public function getProductVariants(int $productId, array $params = []): array
    {
        $endpoint = sprintf('/admin/products/%s/variants.json', $productId);

        return $this->getAllWithMultipleRequests($endpoint, 'variants', $params);
    }

    public function getProductsCount(array $params = []): int
    {
        $result = $this->client->rest('GET', '/admin/products/count.json', $params);

        return $this->getCount($result);
    }

    public function getProductVariantsCount(int $productId): int
    {
        $result = $this->client->rest('GET', sprintf('/admin/products/%s/variants/count.json', $productId));

        return $this->getCount($result);
    }

    public function getProductCollections(): array
    {
        $result = $this->client->rest('GET', '/admin/api/collection_listings.json');

        return $this->getMany($result, 'collection_listings');
    }

    public function getProductCollets(array $params): array
    {
        return $this->getAllWithMultipleRequests('/admin/api/collects.json', 'collects', $params);
    }

    private function getPaginatedItems(string $endpoint, string $resourceName, array $params = [], string $pageInfo = null): PaginatedItems
    {
        // When the page info is present, Shopify does not allow other parameters than the limit.
        // All other filters are already encoded in the page info.
        if ($pageInfo) {
            $params = [
                'page_info' => $pageInfo,
                'limit' => $params['limit'] ?? self::LIMIT_DEFAULT
            ];
        }

        $result = $this->client->rest('GET', $endpoint, $params);

        return (new PaginatedItems($this->getMany($result, $resourceName)))
            ->setNextLink($result->link->next ?? null)
            ->setPreviousLink($result->link->previous ?? null);
    }

    private function getCount($result): int
    {
        if (!$result->errors) {
            return $result->body->count ?? 0;
        }

        throw new ShopifyApiException($result->exception->getMessage(), 0, $result->exception);
    }

    private function getMany($result, string $resourceName): array
    {
        if (!$result->errors) {
            return $result->body->{$resourceName} ?? [];
        }

        throw new ShopifyApiException($result->exception->getMessage(), 0, $result->exception);
    }

    private function getSingle($result, string $resourceName)
    {
        if (!$result->errors) {
            return $result->body->{$resourceName} ?? null;
        }

        // We return null if we hit a 404, otherwise throw an exception.
        if ($result->status === 404) {
            return null;
        }

        throw new ShopifyApiException($result->exception->getMessage(), 0, $result->exception);
    }

    private function getAllWithMultipleRequests(string $endpoint, string $resourceName, array $params): array
    {
        // Shopify returns at max 250 products per API request, so we might have to perform multiple ones.
        if (!isset($params['limit'])) {
            $params = array_merge($params, ['limit' => self::LIMIT_MAX]);
        }

        $paginatedItems = $this->getPaginatedItems($endpoint, $resourceName, $params);
        $items = collect($paginatedItems->getItems());

        while ($paginatedItems->getNextLink()) {
            $paginatedItems = $this->getPaginatedItems($endpoint, $resourceName, $params, $paginatedItems->getNextLink());
            $items = $items->merge(collect($paginatedItems->getItems()));
        }

        return $items->values()->all();
    }
}
