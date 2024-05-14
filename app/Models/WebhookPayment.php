<?php

namespace App\Models;

use App\helpers\Helper;
use DateTime;
use DateTimeZone;
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
        $webhook->date_created  = self::toMySQL($data['date_created'], true);
        $webhook->user_id       = $data['user_id'];
        $webhook->payment_id    = $data['id'];
        $webhook->save();

        return $webhook;
    }

    const MYSQL_DATE_FORMAT = 'Y-m-d';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function toMySQL($date, $time = FALSE, $fromTimeZone = 'UTC', $toTimeZone = 'America/Sao_Paulo') {
        if (empty(trim($date))) return NULL;
        $format = $time ? self::MYSQL_DATETIME_FORMAT : self::MYSQL_DATE_FORMAT;

        $dt = new DateTime($date, new DateTimeZone($fromTimeZone));

        $dt->setTimezone(new DateTimeZone($toTimeZone));

        return $dt->format($format);
    }
}
