<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GiftPayment
 *
 * @property string $payment_id
 * @property string $user_id
 * @property string $presente_id
 * @property string $valor
 * @property string $status
 * @property string $url
 * @property string $dt_created
 * @property string $dt_updated
 */

class GiftPayment extends Model {

    protected $table = 'gift_payment';

    public $timestamps = false;

    protected $hidden = [];

    protected $casts = [];
}
