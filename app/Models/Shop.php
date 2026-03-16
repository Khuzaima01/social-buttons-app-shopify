<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shop extends Model
{
    use HasUuids;

    protected $fillable = [
        'shop',
        'access_token',
        'scope',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function getUuid(): ?string
    {
        return $this->id;
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    public function getShopDomain(): ?string
    {
        return $this->shop;
    }

    public function getShop(): ?string
    {
        return $this->shop;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getTokenExpiresAt(): ?Carbon
    {
        return $this->token_expires_at;
    }

    public function isTokenExpired(): ?bool
    {
        return $this->token_expires_at ? $this->token_expires_at->isPast() : false;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }
}
