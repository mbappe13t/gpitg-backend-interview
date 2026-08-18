<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRating extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'rating_datetime',
    ];

  
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

  
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'rating_datetime' => 'datetime:Y-m-d H:i:s',
        ];
    }
}
