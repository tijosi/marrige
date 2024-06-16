<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftPayment extends Model
{

    protected $table = 'gift_payment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'presente_id',
        'valor',
        'status',
        'url',
        'dt_created',
        'dt_updated',
    ];

    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

}
