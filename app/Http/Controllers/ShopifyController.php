<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ShopifyController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService,
    ) {}

    public function index(Request $request)
    {
        $shopDomain = $request->attributes->get('shopDomain');
        $shop = $request->attributes->get('shop');

        if (!$shopDomain) {
            return response('Missing shop parameter', 400);
        }

        if (!$shop) {
            return response('Shop not found.', 404);
        }

        // Validate Access Token
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->getAccessToken(),
        ])->get("https://{$shopDomain}/admin/api/2026-01/shop.json");

        if ($response->failed()) {
            return redirect('/api/auth?shop=' . $shopDomain);
        }

        return redirect('/whatsapp-settings?shop=' . $shopDomain);
    }


    public function redirectToShopify(Request $request)
    {
        $shop = $request->shop;

        Log::channel('shopify')->info('I am here');

        if (!$shop) {
            return redirect()->back()->with('error', 'Missing shop parameter.');
        }

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('shopify.shopify_api_key'),
            'scope' => config('shopify.shopify_api_scopes'),
            'redirect_uri' => config('shopify.shopify_redirect_url'),
            'state' => Session::getId(),
            'grant_options[]' => 'per-user',
        ]);

        return redirect($installUrl);
    }

    public function handleCallback(Request $request)
    {
        $shopDomain = $request->shop;
        $shop = $request->shop;
        $code = $request->code;
        $hmac = $request->hmac;

        if (!$shop || !$code || !$hmac) {
            abort(400, 'Missing required OAuth parameters.');
        }

        if (!$this->verifyHmac($request->all(), $hmac)) {
            abort(400, 'Invalid HMAC signature.');
        }

        $accessToken = $this->getAccessToken($shop, $code);

        // Access Token
        $token = $accessToken['token'] ?? null;
        $scope = $accessToken['scope'] ?? null;
        $expiresIn = $accessToken['expires_in'] ?? null;


        Shop::updateOrCreate(
            ['shop' => $shopDomain],
            [
                'access_token' => $token,
                'scope' => $scope,
                'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            ]
        );

        return redirect('whatsapp-settings?shop=' . $shopDomain);
    }

    private function verifyHmac($params, $hmac)
    {
        unset($params['hmac'], $params['signature']);
        ksort($params);

        $computedHmac = hash_hmac('sha256', http_build_query($params), config('shopify.shopify_api_secret'));
        return hash_equals($hmac, $computedHmac);
    }

    private function getAccessToken($shop, $code)
    {
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('shopify.shopify_api_key'),
            'client_secret' => config('shopify.shopify_api_secret'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            abort(500, 'Failed to get access token.');
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        return [
            'token' => $data['access_token'] ?? null,
            'scope' => $data['scope'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
        ];
    }
}
