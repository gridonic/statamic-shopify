<?php

namespace Statamic\Addons\Shopify\Model;

class PaginatedItems
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var string
     */
    private $nextLink;

    /**
     * @var string
     */
    private $previousLink;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getNextLink(): ?string
    {
        return $this->nextLink;
    }

    public function getPreviousLink(): ?string
    {
        return $this->previousLink;
    }

    public function setNextLink(?string $nextLink): PaginatedItems
    {
        $this->nextLink = $nextLink;

        return $this;
    }

    public function setPreviousLink(?string $previousLink): PaginatedItems
    {
        $this->previousLink = $previousLink;

        return $this;
    }
}
