<?php

namespace Statamic\Addons\Shopify\tests;

trait MockTrait
{
    private function getMockedProducts(): array
    {
        return collect([1, 2, 3])->map(function ($index) {
            $product = new \stdClass();
            $product->id = $index;
            $product->title = 'Title ' . $index;
            $product->product_type = 'Type ' . $index;

            return $product;
        })->values()->all();
    }
}
