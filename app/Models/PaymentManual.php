<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PaymentManual
 *
 * @property string $user_id
 * @property string $presente_id
 * @property string $valor
 * @property string $status
 */

class PaymentManual extends Model
{
    use HasFactory;

    protected $table = 'payment_manual';

}
