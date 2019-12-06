<?php

namespace Statamic\Addons\Shopify\Event;

use Statamic\Events\Event;

class ProductsSuggestionsEvent extends Event
{
    /**
     * @var array
     */
    private $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): ProductsSuggestionsEvent
    {
        $this->filters = $filters;

        return $this;
    }
}
