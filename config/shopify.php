<?php

return [
    'shopify_api_key' => env('SHOPIFY_API_KEY', ''),
    'shopify_api_secret' => env('SHOPIFY_API_SECRET', ''),
    'shopify_api_scopes' => env('SHOPIFY_API_SCOPES', 'read_orders,write_products,write_pixels'),
    'shopify_app_url' => env('SHOPIFY_APP_URL', ''),
    'shopify_redirect_url' => env('SHOPIFY_REDIRECT_URL', ''),
];
