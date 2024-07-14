<?php

namespace App\Models;

use App\Http\Api\MercadoPagoApiService;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class Presente extends Model
{

    protected $table = 'presentes';

    const vlrMinParcelaCota = 100;

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
        'payment_url',
        'vlr_presenteado',
        'vlr_processando'
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
    const COTA = 'COTA';

    public function verificaPresente() {
        $payments = GiftPayment::where('presente_id', '=', $this->id)->get();

        $valorPago      = null;
        $valorPendente  = null;
        if ($payments->isNotEmpty()) {
            $api            = new MercadoPagoApiService();
            foreach ($payments as $payment) {
                $paymentApi = $api->buscarPagamento($payment->payment_id);
                $quantidade = $paymentApi->additional_info->items[0]->quantity;

                if ($payment->status == MercadoPagoApiService::APROVADO) {
                    $valorPago += $paymentApi->additional_info->items[0]->unit_price * $quantidade;
                    continue;
                }

                if ($payment->status == MercadoPagoApiService::PENDENTE) {
                    if (
                        $paymentApi->status_detail == MercadoPagoApiService::PAGAMENTO_EM_PROCESSADO  ||
                        $paymentApi->status_detail == MercadoPagoApiService::EM_ANALISE               ||
                        $paymentApi->status_detail == MercadoPagoApiService::PENDENTE_TRANSFERENCIA
                    ) {
                        $valorPendente += $paymentApi->additional_info->items[0]->unit_price * $quantidade;
                        continue;
                    };

                    $dtNow = new DateTime();
                    $dtCreation = new DateTime($payment->dt_updated);

                    if ($dtNow->diff($dtCreation)->days >= 1) {
                        $pagamento = $api->cancelaPagamento($payment->payment_id);

                        $payment->status = MercadoPagoApiService::CANCELADO;
                        $payment->save();
                    }
                }
            }
        }

        $valorDisponivel = abs(($valorPago + $valorPendente) - ($this->valor_min + $this->valor_max)/2);
        if ($valorDisponivel < 0.3) {
            $this->flg_disponivel = 0;
        } else {
            $this->flg_disponivel = 1;
        }

        $this->vlr_processando = $valorPendente;
        $this->vlr_presenteado = $valorPago;
        $this->desconfiguraParametros();
        $this->save();
    }

    private function desconfiguraParametros() {
        if (isset($this->valor))                unset($this->valor);
        if (isset($this->cotas))                unset($this->cotas);
        if (isset($this->cotas_disponiveis))    unset($this->cotas_disponiveis);
        if (isset($this->vlr_cota))             unset($this->vlr_cota);
    }

    public function configuraParametros() {
        $this->valor = ($this->valor_min + $this->valor_max)/2;
        $this->setCota();
    }

    public function setCota() {
        $valor = ($this->valor_min + $this->valor_max)/2;
        if($valor < self::vlrMinParcelaCota) return;

        $valorRetirar = $this->vlr_presenteado + $this->vlr_processando;

        $this->cotas                = intdiv($valor, self::vlrMinParcelaCota);
        $this->cotas_disponiveis    = round(($valor - $valorRetirar)/self::vlrMinParcelaCota, 0);
        $this->vlr_cota             = ($valor - $valorRetirar) / $this->cotas_disponiveis;
    }
}
