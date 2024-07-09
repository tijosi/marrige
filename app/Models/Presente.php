<?php

namespace App\Models;

use App\Http\Api\MercadoPagoApiService;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class Presente extends Model
{

    protected $table = 'presentes';

    const vlrMinParcelaCota = 1500;

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

    public function verificaPresente() {
        if ($this->flg_disponivel == 0 && $this->tipo_selected == self::VALOR) {
            $payment = GiftPayment::where('url', '=', $this->payment_url)->first();

            if ($payment->status == MercadoPagoApiService::APROVADO) return;

            if ($payment->status == MercadoPagoApiService::PENDENTE) {
                $api = new MercadoPagoApiService();
                $paymentApi = $api->buscarPagamento($payment->payment_id);

                if (
                    $paymentApi->status_detail == MercadoPagoApiService::PAGAMENTO_EM_PROCESSADO  ||
                    $paymentApi->status_detail == MercadoPagoApiService::EM_ANALISE
                ) return;

                $dtNow = new DateTime();
                $dtCreation = new DateTime($payment->dt_updated);

                if ($dtNow->diff($dtCreation)->days >= 1) {
                    $pagamento = $api->cancelaPagamento($payment->payment_id);

                    if ($pagamento->status == MercadoPagoApiService::CANCELADO) {
                        $this->flg_disponivel       = 1;
                        $this->payment_url          = null;
                        $this->tipo_selected        = null;
                        $this->name_selected_id     = null;
                        $this->selected_at          = null;
                        $this->tipo_selected        = null;
                        $this->save();

                        $payment->status = MercadoPagoApiService::CANCELADO;
                        $payment->save();
                    }
                }
            }
        }
    }

    public function configuraParametros() {
        $this->verificaPresente();
        $this->valor = round(($this->valor_min + $this->valor_max)/2, 2);
        $this->tags = json_decode($this->tags);
        $this->setCota();
    }

    public function setCota() {
        $valor = ($this->valor_min + $this->valor_max)/2;
        if($valor < self::vlrMinParcelaCota) return;

        $this->cotas    = intdiv($valor, self::vlrMinParcelaCota);
        $this->vlr_cota = round($valor / $this->cotas, 2);
    }
}
