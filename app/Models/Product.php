<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'sku', 'name', 'description', 'photo_path', 'price', 'tax_rate', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null);
    }

    /**
     * The price customers actually recognize from the printed menu — the
     * stored `price` is tax-exclusive so order totals can add tax without
     * double-counting, but the catalogue must display the menu's own price.
     */
    public function priceTtc(): float
    {
        return round((float) $this->price * (1 + (float) $this->tax_rate / 100), 2);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class)->latest('created_at');
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }
}
