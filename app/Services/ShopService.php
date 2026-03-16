<?php

namespace App\Services;

use App\Models\Shop;
use App\Repositories\Common\ShopRepository;
use Illuminate\Http\Request;

class ShopService
{
    public function __construct(
        private readonly ShopRepository $shops
    ) {}

    /**
     * Fetch a shop by its myshopify domain string.
     *
     * @param  string  $shopDomain
     * @return Shop|null
     */
    public function findByDomain(string $shopDomain): ?Shop
    {
        return $this->shops->findByDomain($shopDomain);
    }

    /**
     * Resolve a shop domain from the request using ordered sources.
     *
     * Supported sources: attributes, query, header, input, session.
     * Example values: attributes:shopDomain, query:shop, header:X-Shopify-Shop-Domain.
     *
     * @param  Request  $request
     * @param  array<int, string>  $sources
     * @return string|null
     */
    public function resolveDomain(Request $request, array $sources): ?string
    {
        foreach ($sources as $source) {
            $domain = $this->domainFromSource($request, $source);
            if (is_string($domain) && $domain !== '') {
                return $domain;
            }
        }

        return null;
    }

    /**
     * Resolve the shop domain for a single source.
     */
    private function domainFromSource(Request $request, string $source): ?string
    {
        [$type, $key] = array_pad(explode(':', $source, 2), 2, null);
        $key = $key ?? 'shopDomain';

        return match ($type) {
            'attributes' => $request->attributes->get($key),
            'query' => $request->query($key),
            'header' => $request->header($key),
            'input' => $request->input($key),
            'session' => $request->session()->get($key),
            default => null,
        };
    }
}
