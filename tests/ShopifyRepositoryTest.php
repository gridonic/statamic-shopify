<?php

namespace Statamic\Addons\Shopify\tests;

use Statamic\Addons\Shopify\Exception\ShopifyApiException;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Testing\TestCase;

/**
 * @group shopify
 * @coversDefaultClass \Statamic\Addons\Shopify\Service\ShopifyRepository
 */
class ShopifyRepositoryTest extends TestCase
{
    use MockTrait;

    /**
     * @var \Statamic\Addons\Shopify\Service\ShopifyRepository
     */
    private $shopifyRepository;

    public function setUp()
    {
        parent::setUp();

        $this->shopifyRepository = new ShopifyRepository(new BasicShopifyAPIMock(true));
    }

    /**
     * @test
     */
    public function it_should_find_products()
    {
        $products = $this->shopifyRepository->getProducts(['limit' => 3]);

        $this->assertEquals($this->getMockedProducts(), $products);
    }

    /**
     * @test
     */
    public function it_should_find_paginated_products()
    {
        $paginatedItems = $this->shopifyRepository->getProductsPaginated();

        $this->assertEquals($this->getMockedProducts(), $paginatedItems->getItems());
        $this->assertEquals('nextLink', $paginatedItems->getNextLink());
        $this->assertEquals('previousLink', $paginatedItems->getPreviousLink());
    }

    /**
     * @test
     */
    public function it_should_find_a_single_product()
    {
        $product = $this->shopifyRepository->getProduct(1);

        $this->assertInstanceOf(\stdClass::class, $product);
        $this->assertEquals(1, $product->id);
        $this->assertEquals('Type 1', $product->product_type);
        $this->assertEquals('Title 1', $product->title);
    }

    /**
     * @test
     */
    public function it_should_return_null_if_a_product_is_not_found()
    {
        $product = $this->shopifyRepository->getProduct(111);

        $this->assertNull($product);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_on_api_errors()
    {
        $this->expectException(ShopifyApiException::class);

        $this->shopifyRepository->getProductVariant(-99);
    }

    /**
     * @test
     */
    public function it_should_return_the_number_of_products()
    {
        $this->assertEquals(1000, $this->shopifyRepository->getProductsCount());
    }

    /**
     * @test
     */
    public function it_should_return_the_number_of_product_variations()
    {
        $this->assertEquals(100, $this->shopifyRepository->getProductVariantsCount(1));
    }
}
