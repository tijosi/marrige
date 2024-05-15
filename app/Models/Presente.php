<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

    const PRESENTE = 'PRESENTE';
    const VALOR = 'VALOR';

    public static function verificaPresente(self $presente) {
        if ($presente->flg_disponivel == 0 && $presente->tipo_selected == self::VALOR) {
            $payment = GiftPayment::where('url', '=', $presente->payment_url)->first();

            if ($payment->status == GiftPayment::APROVADO) return $presente;

            if ($payment->status == GiftPayment::EM_PROGRESSO) {
                $paymentApi = GiftPayment::buscarPagamento($payment->payment_id);

                if (
                    $paymentApi->status_detail == GiftPayment::PENDENTE_TRANSFERENCIA   ||
                    $paymentApi->status_detail == GiftPayment::PAGAMENTO_EM_PROCESSADO  ||
                    $paymentApi->status_detail == GiftPayment::EM_ANALISE
                ) return $presente;

                $dtNow = new DateTime();
                $dtCreation = new DateTime($payment->dt_created);

                if ($dtNow->diff($dtCreation) >= 1) {
                    $cancelado = GiftPayment::cancelaPagamento($payment->payment_id);

                    if ($cancelado) {
                        $presente->flg_disponivel       = 1;
                        $presente->payment_url          = null;
                        $presente->tipo_selected        = null;
                        $presente->name_selected_id     = null;
                        $presente->selected_at          = null;
                        $presente->tipo_selected        = null;
                        $presente->save();

                        $payment->status = GiftPayment::CANCELADO;
                        $payment->save();
                    }
                }
            }
        }
        return $presente;
    }
}
