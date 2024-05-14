<?php

namespace App\Models;

use App\helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookPayment extends Model
{

    protected $table = 'webhook_payment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'action',
        'api_version',
        'date_created',
        'user_id',
        'payment_id'
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

    public static function salvarWebhook(Request $request) {
        $data = $request->input();

        if ($data['action'] == 'payment.created') {
            $payment = GiftPayment::latest('id')->first();
            if (!empty($payment)) {
                $payment->payment_id = $data['data']['id'];
            }
        } else {
            $payment = GiftPayment::where('id', '=', $data['data']['id'])->first();
            $response = Http::get("https://api.mercadopago.com/v1/payments/{$payment->payment_id}");
            $payment->status = json_decode($response->body())->status;
            $payment->dt_updated = Helper::toMySQL($data['date_created'], true);
            $payment->save();
        }

        $webhook = new WebhookPayment();
        $webhook->action        = $data['action'];
        $webhook->api_version   = $data['api_version'];
        $webhook->date_created  = Helper::toMySQL($data['date_created'], true);
        $webhook->user_id       = $data['user_id'];
        $webhook->payment_id    = $data['id'];
        $webhook->save();

        return $webhook;
    }
}
