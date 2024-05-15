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
        'payment_id',
        'json',
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


        $paymentApi = GiftPayment::buscarPagamento($data['data_id']);
        if ($data['action'] == 'payment.created') {
            $payment = new GiftPayment();
            $payment->payment_id    = $data['data_id'];
            $payment->user_id       = trim(explode(' - ', $paymentApi->additional_info->payer->first_name)[0] ?? '');
            $payment->presente_id   = $paymentApi->additional_info->items[0]->id;
            $payment->valor         = $paymentApi->additional_info->items[0]->unit_price;
            $payment->status        = $paymentApi->status;
            $payment->url           = $paymentApi->point_of_interaction->transaction_data->ticket_url;
            $payment->dt_created    = Helper::toMySQL('now', true);
            $payment->dt_updated    = Helper::toMySQL('now', true);
            $payment->save();

            $presente = Presente::where('id', '=', $payment->presente_id)->first();
            $presente->flg_disponivel = 0;
            $presente->name_selected_id     = $payment->user_id;
            $presente->selected_at          = Helper::toMySQL('now', true);
            $presente->tipo_selected        = Presente::VALOR;
            $presente->save();
        } else if ($data['action'] == 'payment.updated') {
            $payment = GiftPayment::where('payment_id', '=', $data['data_id'])->first();
            $payment->status = $paymentApi->status;
            $payment->dt_updated = Helper::toMySQL('now', true);
            $payment->save();
        }

        $webhook = new WebhookPayment();
        $webhook->action        = $data['action'];
        $webhook->api_version   = $data['api_version'];
        $webhook->date_created  = Helper::toMySQL($data['date_created'], true);
        $webhook->user_id       = $data['user_id'];
        $webhook->payment_id    = $data['data_id'];
        $webhook->json          = json_encode($data);
        $webhook->save();

        return $webhook;
    }

}
