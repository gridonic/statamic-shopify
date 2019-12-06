<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Events\Event;

class CheckoutCreateEvent extends Event
{
    private $id;

    /**
     * @var string
     */
    private $url;

    /**
     * Additional data saved in the session.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $cartItems = [];

    public function __construct($id, string $url, array $cartItems)
    {
        $this->id = $id;
        $this->url = $url;
        $this->cartItems = $cartItems;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): CheckoutCreateEvent
    {
        $this->url = $url;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(?array $data): CheckoutCreateEvent
    {
        $this->data = $data;

        return $this;
    }

    public function getCartItems(): array
    {
        return $this->cartItems;
    }
}
