<?php

namespace App\Models;

use App\Http\Api\MercadoPagoApiService;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class Presente extends Model
{

    protected $table = 'presentes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'valor_min',
        'valor_max',
        'level',
        'name_img',
        'img_url',
        'name_selected_id',
        'flg_disponivel',
        'selected_at',
        'tipo_selected',
        'payment_url'
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

    const PRODUTO = 'PRODUTO';
    const VALOR = 'VALOR';

    public static function verificaPresente(self $presente) {
        if ($presente->flg_disponivel == 0 && $presente->tipo_selected == self::VALOR) {
            $payment = GiftPayment::where('url', '=', $presente->payment_url)->first();

            if ($payment->status == MercadoPagoApiService::APROVADO) return $presente;

            if ($payment->status == MercadoPagoApiService::PENDENTE) {
                $api = new MercadoPagoApiService();
                $paymentApi = $api->buscarPagamento($payment->payment_id);

                if (
                    $paymentApi->status_detail == MercadoPagoApiService::PAGAMENTO_EM_PROCESSADO  ||
                    $paymentApi->status_detail == MercadoPagoApiService::EM_ANALISE
                ) return $presente;

                $dtNow = new DateTime();
                $dtCreation = new DateTime($payment->dt_updated);

                if ($dtNow->diff($dtCreation)->days >= 1) {
                    $pagamento = $api->cancelaPagamento($payment->payment_id);

                    if ($pagamento->status == MercadoPagoApiService::CANCELADO) {
                        $presente->flg_disponivel       = 1;
                        $presente->payment_url          = null;
                        $presente->tipo_selected        = null;
                        $presente->name_selected_id     = null;
                        $presente->selected_at          = null;
                        $presente->tipo_selected        = null;
                        $presente->save();

                        $payment->status = MercadoPagoApiService::CANCELADO;
                        $payment->save();
                    }
                }
            }
        }
        return $presente;
    }
}
