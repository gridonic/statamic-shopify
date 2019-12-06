<?php

namespace Statamic\Addons\Shopify\tests;

use Statamic\Addons\Shopify\Model\CartItem;
use Statamic\Addons\Shopify\Service\CartSerializer;
use Statamic\Addons\Shopify\Service\ProductVariantSerializer;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Testing\TestCase;

/**
 * @group shopify
 * @coversDefaultClass \Statamic\Addons\Shopify\Service\CartSerializer
 */
class CartSerializerTest extends TestCase
{
    /**
     * @var \Statamic\Addons\Shopify\Service\CartSerializer
     */
    private $cartSerializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $shopifyRepositoryMock;

    public function setUp()
    {
        parent::setUp();

        $this->shopifyRepositoryMock = $this->getMockBuilder(ShopifyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartSerializer = new CartSerializer($this->shopifyRepositoryMock, new ProductVariantSerializer());
    }

    /**
     * @test
     */
    public function it_should_serialize_the_items()
    {
        $variant1 = (object) ['id' => 1, 'title' => 'Variant 1'];
        $variant2 = (object) ['id' => 2, 'title' => 'Variant 2'];

        $this->shopifyRepositoryMock
            ->method('getProductVariant')
            ->willReturnOnConsecutiveCalls($variant1, $variant2);

        $cartItem1 = new CartItem(1, 2);
        $cartItem2 = new CartItem(2, 5);

        $expected = [
            [
                'quantity' => $cartItem1->getQuantity(),
                'variant' => (array)$variant1,
            ],
            [
                'quantity' => $cartItem2->getQuantity(),
                'variant' => (array)$variant2,
            ],
        ];

        $this->assertEquals($expected, $this->cartSerializer->serializeCartItems([$cartItem1, $cartItem2]));
    }

    /**
     * @test
     */
    public function it_should_remove_non_existing_variants()
    {
        $this->shopifyRepositoryMock
            ->method('getProductVariant')
            ->willReturn(null);

        $cartItems = [new CartItem(100, 3)];

        $this->assertEmpty($this->cartSerializer->serializeCartItems($cartItems));
    }

}
