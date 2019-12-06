<?php

namespace Statamic\Addons\Shopify\tests;

use OhMyBrew\BasicShopifyAPI;

class BasicShopifyAPIMock extends BasicShopifyAPI
{
    use MockTrait;

    public function rest(string $type, string $path, array $params = null, array $headers = [], bool $sync = true) {
        if ($path === '/admin/products.json' && count($params)) {
            return $this->getSuccessResult([
                'products' => $this->getMockedProducts()
            ]);
        } elseif ($path === '/admin/products.json' && !count($params)) {
            return $this->getSuccessResultWithPageLinks([
                'products' => $this->getMockedProducts()
            ]);
        } elseif ($path === '/admin/products/1.json') {
            return $this->getSuccessResult([
                'product' => collect($this->getMockedProducts())->first()
            ]);
        } elseif ($path === '/admin/products/1/variants/count.json') {
            return $this->getSuccessResult([
                'count' => 100,
            ]);
        } elseif ($path === '/admin/products/count.json') {
            return $this->getSuccessResult([
                'count' => 1000,
            ]);
        } elseif ($path === '/admin/products/111.json') {
            return $this->getErrorResult(404);
        } elseif ($path === '/admin/variants/-99.json') {
            return $this->getErrorResult(500);
        }
    }

    private function getErrorResult(int $status)
    {
        return (object)[
            'errors' => true,
            'status' => $status,
            'exception' => new \Exception('This message does not really matter!'),
        ];
    }

    private function getSuccessResult(array $body)
    {
        return (object) [
            'errors'     => false,
            'body'       => (object) $body,
        ];
    }

    private function getSuccessResultWithPageLinks(array $body)
    {
        return (object) [
            'errors'     => false,
            'body'       => (object) $body,
            'link' => [
                'next' => 'nextLink',
                'previous' => 'previousLink',
            ],
        ];
    }

}
