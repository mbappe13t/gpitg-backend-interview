<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public $timestamps = false;


    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    public function ratings(): HasMany
    {
        return $this->hasMany(UserRating::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
