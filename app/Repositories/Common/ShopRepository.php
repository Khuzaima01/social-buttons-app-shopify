<?php

namespace App\Repositories\Common;

use App\Models\Shop;
use App\Repositories\BaseRepository;

class ShopRepository extends BaseRepository
{
    /**
     * Return the model class name for this repository.
     *
     * @return string
     */
    public function model(): string
    {
        return Shop::class;
    }

    /**
     * Locate a shop by its myshopify domain string.
     *
     * @param  string  $shopDomain
     * @return Shop|null
     */
    public function findByDomain(string $shopDomain): ?Shop
    {
        return $this->model->where('shop', $shopDomain)->first();
    }
}
