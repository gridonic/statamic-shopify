<?php

namespace Statamic\Addons\Shopify\tests;

use Statamic\Addons\Shopify\Model\CartItem;
use Statamic\Addons\Shopify\Service\CheckoutManager;
use Statamic\Testing\TestCase;

/**
 * @group shopify
 * @coversDefaultClass \Statamic\Addons\Shopify\Service\CheckoutManager
 */
class CheckoutManagerTest extends TestCase
{
    /**
     * @var \Statamic\Addons\Shopify\Service\CheckoutManager
     */
    private $checkoutManager;

    public function setUp()
    {
        parent::setUp();

        $this->checkoutManager = new CheckoutManager(new CheckoutClientMock());

        // Reset anything in the session before starting a test.
        session()->forget(CheckoutManager::SESSION_KEY);
    }

    /**
     * @test
     */
    public function it_should_return_null_if_no_checkout_exists()
    {
        $this->assertNull($this->checkoutManager->getId());
        $this->assertNull($this->checkoutManager->getUrl());
    }

    /**
     * @test
     */
    public function it_should_create_and_update_checkouts()
    {
        $item1 = new CartItem(100, 2);
        $item2 = new CartItem(200, 3);

        $this->checkoutManager->createOrUpdate([$item1, $item2]);

        $this->assertEquals('checkoutId', $this->checkoutManager->getId());
        $this->assertEquals('checkoutUrl', $this->checkoutManager->getUrl());

        $newItem = new CartItem(300, 4);

        $this->checkoutManager->createOrUpdate([$newItem]);

        $this->assertEquals('checkoutId', $this->checkoutManager->getId());
        $this->assertEquals('checkoutUrl', $this->checkoutManager->getUrl());
    }

    /**
     * @test
     */
    public function it_should_clear_session_data()
    {
        $item1 = new CartItem(1, 1);
        $this->checkoutManager->createOrUpdate([$item1]);

        $this->checkoutManager->clear();

        $this->assertNull($this->checkoutManager->getId());
        $this->assertNull($this->checkoutManager->getUrl());
    }
}
