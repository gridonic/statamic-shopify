<?php

namespace Statamic\Addons\Shopify;

use Illuminate\Http\JsonResponse;
use Statamic\Addons\Shopify\Model\CartItem;
use Statamic\Addons\Shopify\Service\CartManager;
use Statamic\Addons\Shopify\Service\CartSerializer;
use Statamic\Addons\Shopify\Service\ShopifyRepository;
use Statamic\Extend\Controller;
use Statamic\Extend\Extensible;
use Statamic\Exceptions\UrlNotFoundException;

class ShopifyController extends Controller
{
    use Extensible;

    /**
     * @var \Statamic\Addons\Shopify\Service\CartManager
     */
    private $cartManager;

    /**
     * @var \Statamic\Addons\Shopify\Service\ShopifyRepository
     */
    private $client;

    /**
     * @var \Statamic\Addons\Shopify\Service\CartSerializer
     */
    private $cartSerializer;

    public function __construct(CartManager $cartManager, ShopifyRepository $client, CartSerializer $cartSerializer)
    {
        parent::__construct();

        $this->cartManager = $cartManager;
        $this->client = $client;
        $this->cartSerializer = $cartSerializer;
    }

    public function getCart()
    {
        $this->throw404IfDisabled();

        $cartItems = $this->cartManager->get();

        return new JsonResponse($this->cartSerializer->serializeCartItems($cartItems));
    }

    public function postCart($variationId, $quantity)
    {
        $this->throw404IfDisabled();

        $strategy = request()->get('strategy', CartManager::STRATEGY_MERGE);
        $cartItem = new CartItem((int)$variationId, (int)$quantity);

        $this->cartManager->store($cartItem, $strategy);

        return new JsonResponse();
    }

    public function postCartRemove($variationId)
    {
        $this->throw404IfDisabled();

        $this->cartManager->remove((int)$variationId);

        return new JsonResponse();
    }

    public function postCartClear()
    {
        $this->throw404IfDisabled();

        $this->cartManager->clear();

        return new JsonResponse();
    }

    public function getCartCount()
    {
        $this->throw404IfDisabled();

        $perVariation = request()->get('perVariation', false);

        if ($perVariation) {
            $count = collect($this->cartManager->get())->count();
            return new JsonResponse($count);
        }

        $count = collect($this->cartManager->get())->map(function ($cartItem) {
            /** @var $cartItem CartItem */
            return $cartItem->getQuantity();
        })->sum();

        return new JsonResponse($count);
    }

    private function throw404IfDisabled()
    {
        if (!$this->getConfigBool('cart_api_enable', false)) {
            throw new UrlNotFoundException();
        }
    }
}
