<?php

namespace Statamic\Addons\Shopify\tests;

use Statamic\Addons\Shopify\Model\CartItem;
use Statamic\Addons\Shopify\Service\CartManager;
use Statamic\Testing\TestCase;

/**
 * @group shopify
 * @coversDefaultClass \Statamic\Addons\Shopify\Service\CartManager
 */
class CartManagerTest extends TestCase
{
    /**
     * @var \Statamic\Addons\Shopify\Service\CartManager
     */
    private $cartManager;

    public function setUp()
    {
        parent::setUp();

        $this->cartManager = new CartManager();

        // Reset anything in the cart before starting a test.
        session()->forget(CartManager::SESSION_KEY);
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_array_if_no_items()
    {
        $this->assertEquals([], $this->cartManager->get());
    }

    /**
     * @test
     */
    public function it_should_return_the_stored_items()
    {
        $item1 = new CartItem(100, 2);
        $item2 = new CartItem(200, 3);

        $this->cartManager->store($item1);
        $this->cartManager->store($item2);

        // The keys are equal to the variation ID.
        $expected = [
            100 => $item1,
            200 => $item2,
        ];

        $this->assertEquals($expected, $this->cartManager->get());
    }

    /**
     * @test
     */
    public function it_should_combine_items_when_using_the_merge_strategy()
    {
        $item1 = new CartItem(100, 1);
        $item2 = new CartItem(100, 2);

        $this->cartManager->store($item1, CartManager::STRATEGY_MERGE);
        $this->cartManager->store($item2, CartManager::STRATEGY_MERGE);

        $expectedItem = new CartItem(100, 3);

        $this->assertEquals([100 => $expectedItem], $this->cartManager->get());
    }

    /**
     * @test
     */
    public function it_should_replace_items_when_using_the_replace_strategy()
    {
        $item1 = new CartItem(100, 1);
        $item2 = new CartItem(100, 5);

        $this->cartManager->store($item1, CartManager::STRATEGY_REPLACE);
        $this->cartManager->store($item2, CartManager::STRATEGY_REPLACE);

        $this->assertEquals([100 => $item2], $this->cartManager->get());
    }

    /**
     * @test
     */
    public function it_should_remove_items()
    {
        $item1 = new CartItem(100, 2);
        $item2 = new CartItem(200, 3);

        $this->cartManager->store($item1);
        $this->cartManager->store($item2);

        $this->cartManager->remove($item1->getVariationId());

        $this->assertEquals([200 => $item2], $this->cartManager->get());
    }

    /**
     * @test
     */
    public function it_should_clear_session_data()
    {
        $item1 = new CartItem(100, 2);
        $this->cartManager->store($item1);

        $this->cartManager->clear();

        $this->assertEquals([], $this->cartManager->get());
    }
}
