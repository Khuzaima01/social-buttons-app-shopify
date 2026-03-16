<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Services\ShopService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShop
{
    public function __construct(
        private readonly ShopService $shopService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shopDomain = $this->shopService->resolveDomain($request, ['query:shop', 'session:shop']);

        if (!$shopDomain) {
            return response('Missing shop parameter', 400);
        }

        /** @var Shop $shop */
        $shop = $this->shopService->findByDomain($shopDomain);

        // If shop doesn't exist or doesn't have an access token, redirect to installation flow
        if (!$shop || !$shop->getAccessToken()) {
            return redirect('/api/auth?shop=' . $shopDomain);
        }

        // Store shop info in session and request attributes for later use
        $request->session()->put('shop', $shopDomain);
        $request->attributes->set('shopDomain', $shopDomain);
        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
