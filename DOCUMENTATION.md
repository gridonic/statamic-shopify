## Installation

1. Download the Addon, extract the folder and rename it to `Shopify`
2. Move the `Shopify` folder to `site/addons`
3. Run `php please update:addons` to install the dependencies.

## Configuration

First, you need to create a private Shopify app:

1. Go to your Shopify backend, click on "Apps" and then "Manage private apps".
2. Create a new private app. Make sure open/restrict the admin API permissions according to your needs.
3. If you want to create Shopify checkouts from products in the cart, enable access to the Storefront API as well.

Next, enter the API key, password, shared secret (and eventually the Storefront access token) in the addon's configuration.

That's it! 🎉 You should now be able to display products from Shopify on your website. 

## Content editing

The addon includes two suggest modes allowing you to integrate Shopify content into your website via
[suggest](https://docs.statamic.com/fieldtypes/suggest#suggest-modes) fields.

1. The `shopify.products` suggest mode can be used to select products.
2. The `shopify.productCollections` mode displays Shopify product collections.

**Example**

The following field allows to display a product catalog from a Shopify collection.

```yaml
product_catalog:
    type: suggest
    mode: shopify.productCollections
    max_items: 1
    display: Product catalog
    instructions: Select the Shopify collection containing the products for the catalog. 
```

Then, use the `{{ shopify:products }}` tag to display the products on your website based on
the stored shopify collection id:

```
{{ shopify:products filter="collection_id={product_catalog}" }}
    {{ title }}
{{ /shopify:products }}
```

> **Tip**: You can customize the label of products shown in the control panel suggest fields in the addon configuration.

> **Pro Tip**: You can further customize the products returned by the `shopify.products` suggest mode by
listening to the `Shopify.productsSuggestions` event.

## Antlers tags

### `{{ shopify:products }}`

Allows to loop and display a filtered set of products. Use this tag to display product catalogs!

**Parameters**

* `filter` Key value pair of filters separated with `&`.
See the [docs](https://help.shopify.com/en/api/reference/products/product) for a list of available filters.
* `paginated` True to enable pagination (see example).
* `pagination_query_string` The GET query string used to identify the next or previous page, if pagination is enabled.
* `sort_by_collection` If you filter products by a Shopify product collection, you can sort them according to the
configured sort strategy in Shopify.
* `as_json` True to output the the products as JSON string.

**Examples**

Fetch all published products of type `car` and display the variations and the price.

```
{{ shopify:products filters="product_type=car&published_status=published" }}
    <h2>{{ title }}</h2>
    
    <ul>
    {{ variants }}
        <li>{{ title }}: {{ price }}</li>    
    {{ /variants }}
    </ul> 
{{ /shopify:products }}
```

---

Show all products from the Shopify collection `discounts` and sort them according to the sorting strategy
configured in Shopify.

```
{{ shopify:products
   filters="collection_id=discounts&published_status=published"
   sort_by_collection="true" 
}}
    <h2>{{ title }}</h2>    
{{ /shopify:products }}
```
 
---

Display paginated products with a limit of `50` products per page. The products and pagination variables are
wrapped in two separate variables:

* `products` Array of products of the current page.
* `paginate` Contains the `previous_url` and `next_url` pagination links.

```
{{ shopify:products
   filters="published_status=published&vendor=Statamic"
   paginated="true" 
}}
    {{ products }}    
        <h2>{{ title }}</h2>
    {{ /products }}
    
    {{ paginate }}
        {{ if previous_url }}
            <a href="{{ previous_url }}">Previous page</a>
        {{ /if }}
        {{ if next_url }}
            <a href="{{ next_url }}">Next page</a>
        {{ /if }}
    {{ /paginate }} 
{{ /shopify:products }}
```

> Note: The Shopify API allows a maximum of 250 products per page. Furthermore, it is only possible
to navigate to the next and previous page. 

### `{{ shopify:product_variants }}`

Allows to loop and display all product variants of a given product, optionally filtered with a
given filter string. See the [docs](https://help.shopify.com/en/api/reference/products/product-variant) for all
available filters.

**Parameters**

* `product_id` The product ID
* `filter` Key value pair of filters separated with `&`.
* `as_json` True to output the the product variants as JSON string.

**Examples**

Get all product variants from the product ID stored in the `product_id` variable.

```
{{ shopify:product_variants :product_id="product_id" }}
    <h2>{{ title }}</h2>
    <p>Price: {{ price }}</p>
        
{{ /shopify:product_variants }}
```

### `{{ shopify:product }}`

Get a shopify product either by id or handle.

**Parameters**

* `id` The id of the product.
* `handle` The handle of the product.
* `throw_404` Whether to throw a 404 if the product is not found.
* `as_json` True to output the the product data as JSON string.

**Examples**

Get the product by a handle and display the 404 page if it does not exist.

```
{{ shopify:product
   handle="my-awesome-product"
   throw_404="true"
}}
    <h2>{{ title }}</h2>
    {{ image }}
        <img src={{ src }} alt={{ title }}>
    {{ /image }}
{{ /shopify:product }}
```

### `{{ shopify:product_variant }}`

Get a shopify product variant by id.

**Parameters**

* `id` The id of the product variant.
* `throw_404` Whether to throw a 404 if the product variant is not found.
* `as_json` True to output the the product data as JSON string.

### `{{ shopify:products_count }}`

Returns the number of products given by the provided filter string.

**Parameters**

* `filter` Key value pair of filters separated with `&`.

### `{{ shopify:product_variants_count }}`

Returns the number of product variants of the given product id.

**Parameters**

* `product_id` The id of the product.

## Shopping Cart

The addon ships with a simple cart implementation using the session to store product variants together 
with quantities. Enable the cart API in the addon configuration to expose a HTTP based endpoint.

| Method | URL | Description |
---------|-----|-------------|
| `GET`  | `/!/Shopify/cart` | Get all product variant and their quantity as JSON. |
| `GET`  | `/!/Shopify/cartCount` | Get the total number of products in the cart. |
| `POST` | `/!/Shopify/cart/<variation_id>/<quantity>` | Store the given variation and quantity in the cart. By default, quantities are merged if the variation already exists in the cart. Use the GET parameter `?strategy=replace` to replace the existing variation. |  
| `POST` | `/!/Shopify/cartRemove/<variation_id>` | Remove the given product variation from the cart. |
| `POST` | `/!/Shopify/cartClear` | Remove all product variants in the cart. |

> Do not forget to send along the CSRF token for any `POST` requests.

Alternatively, you can use the `Statamic\Addons\Shopify\Service\CartManager` service to manage the cart
in your own business logic.

### Antlers tags

The following Antlers tags are available related to the shopping cart:

* `{{ shopify:cart_items }}` to loop and display all product variants and quantities in the cart.
* `{{ shopify:cart_count }}` to check whether the cart is empty.
* `{{ shopify:cart_clear }}` to empty the shopping cart.

## Checkout 

It is possible to redirect the customer to the Shopify checkout with the current shopping cart.
In order to use Shopify's checkout API, you need to enable the Storefront API in your private Shopify app
and copy the Storefront API access token to the addon configuration.

**Here is how it works:**

Create the following template routes in your `site/settings/routes.yaml` file:

```yaml
  /checkout: checkout
  /checkout-complete: checkout_complete
``` 

The `checkout.html` template should look like this:

```
{{ if {shopify:cart_count} }}
    {{ shopify:checkout_update }}
    {{ shopify:checkout_redirect }}
{{ else }}
    {{ redirect to="/" }}
{{ /if }}
```

As you can see, the template first checks if there are any products in the cart. If so, it creates or updates
a Shopify checkout based on those products and redirects the user to the external checkout URL. After completing the checkout,
the customer can be redirected back to your website. This is a bit _hacky_, as the redirect needs to be executed via javascript from
Shopify's order confirmation page. The route `/checkout-complete` takes care of clearing the stored cart and checkout data in the session. 
Its associated template `checkout_complete.html` should look like this:

```
{{ shopify:cart_clear }}
{{ shopify:checkout_clear }}
{{ redirect to="/complete" }}
```

In this example, the user is again redirected to a thank you page coming from Statamic. 😎

> Use the `Statamic\Addons\Shopify\Service\CheckoutManager` service to create or update checkouts in your own code.

## Events 

The following events are available for developers to further customize the behaviour of the addon:

* `Shopify.serializeProduct`: Emitted after products have been fetched from the API and prior this data is sent to Antlers. Use it to add, modify
or remove product data available in Antlers.
* `Shopify.serializeProductVariant`: Identical to the event above, but for product variants.
* `Shopify.productsSuggestions`: Allows to customize the filter used to display products in the control panel via `shopify.products` suggest mode. 
* `Shopify.checkoutCreate`: Emitted when creating a checkout.
