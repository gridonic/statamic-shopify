<?php

namespace Statamic\Addons\Shopify;

use OhMyBrew\BasicShopifyAPI;
use Statamic\Addons\Shopify\Service\CartManager;
use Statamic\Addons\Shopify\Service\CartSerializer;
use Statamic\Addons\Shopify\Service\CheckoutClient;
use Statamic\Addons\Shopify\Service\CheckoutManager;
use Statamic\Addons\Shopify\Service\ProductSerializer;
use Statamic\Addons\Shopify\Service\ProductVariantSerializer;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Extend\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    // The API version used for the API calls.
    const SHOPIFY_API_VERSION = '2020-01';

    protected $defer = true;

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->registerShopifyClient();
        $this->registerShopifyRepository();
        $this->registerProductSerializer();
        $this->registerProductVariantSerializer();
        $this->registerCartManager();
        $this->registerCheckoutManager();
        $this->registerCartSerializer();
        $this->registerCheckoutClient();
    }

    private function registerShopifyClient()
    {
        $this->app->singleton(BasicShopifyAPI::class, function () {
            $client = (new BasicShopifyAPI(true))
                ->setVersion(self::SHOPIFY_API_VERSION)
                ->setShop($this->getConfig('store_url'))
                ->setApiKey($this->getConfig('api_key'))
                ->setApiPassword($this->getConfig('password'))
                ->setApiSecret($this->getConfig('shared_secret'));

            if ($this->getConfigBool('rate_limiting_enable', false)) {
                $client->enableRateLimiting();
            }

            return $client;
        });
    }

    private function registerShopifyRepository()
    {
        $this->app->singleton(ShopifyRepository::class, function ($app) {
            return new ShopifyRepository($app[BasicShopifyAPI::class]);
        });
    }

    private function registerProductSerializer()
    {
        $this->app->singleton(ProductSerializer::class, function () {
            return new ProductSerializer();
        });
    }

    private function registerProductVariantSerializer()
    {
        $this->app->singleton(ProductVariantSerializer::class, function () {
            return new ProductVariantSerializer();
        });
    }

    private function registerCartManager()
    {
        $this->app->singleton(CartManager::class, function () {
            return new CartManager();
        });
    }

    private function registerCheckoutManager()
    {
        $this->app->singleton(CheckoutManager::class, function ($app) {
            return new CheckoutManager($app[CheckoutClient::class]);
        });
    }

    private function registerCartSerializer()
    {
        $this->app->singleton(CartSerializer::class, function ($app) {
            return new CartSerializer($app[ShopifyRepository::class], $app[ProductVariantSerializer::class]);
        });
    }

    private function registerCheckoutClient()
    {
        $this->app->singleton(CheckoutClient::class, function ($app) {
            return new CheckoutClient($app[BasicShopifyAPI::class], $this->getConfig('storefront_access_token', ''));
        });
    }
}
