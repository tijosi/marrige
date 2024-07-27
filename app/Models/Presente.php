<?php

namespace App\Models;

use App\Http\Api\MercadoPagoApiService;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Presente
 *
 * @property string $nome
 * @property string $valor
 * @property string $level
 * @property string $name_img
 * @property string $img_url
 * @property string $flg_disponivel
 * @property string $payment_url
 * @property string $vlr_presenteado
 * @property string $vlr_processando
 * @property string $selected_by_user_id
 * @property string $selected_at
 */

class Presente extends Model
{

    protected $table = 'presentes';

    const vlrMinParcelaCota = 1800;

    public $timestamps = false;

    protected $hidden = [];

    protected $casts = [];

    const PRODUTO   = 'PRODUTO';
    const VALOR     = 'VALOR';
    const COTA      = 'COTA';

    public function verificaPresente() {
        $valores = $this->verificaPagamentos();
        $valores->valorPago += $this->verificaPagamentosManuais();

        $valorDisponivel = abs(($valores->valorPago + $valores->valorPendente) - $this->valor);
        if ($valorDisponivel < 0.3) {
            $this->flg_disponivel = 0;
        } else {
            $this->flg_disponivel = 1;
        }

        $this->vlr_processando = $valores->valorPendente;
        $this->vlr_presenteado = $valores->valorPago;
        $this->unsetCota();
        $this->save();
    }

    /**  @return object {valorPago: float, valorPendente: float} */
    private function verificaPagamentos(): object {
        $payments = GiftPayment::where('presente_id', '=', $this->id)->get();

        $valores = (object) [
            'valorPago' => 0,
            'valorPendente' => 0
        ];

        if ($payments->isNotEmpty()) {
            $api            = new MercadoPagoApiService();
            foreach ($payments as $payment) {
                if ($payment->status == MercadoPagoApiService::CANCELADO) continue;

                $paymentApi = $api->buscarPagamento($payment->payment_id);
                $quantidade = $paymentApi->additional_info->items[0]->quantity;

                if ($payment->status == MercadoPagoApiService::APROVADO) {
                    $valores->valorPago += $paymentApi->additional_info->items[0]->unit_price * $quantidade;
                    continue;
                }

                if ($payment->status == MercadoPagoApiService::PENDENTE) {
                    if (
                        $paymentApi->status_detail == MercadoPagoApiService::PAGAMENTO_EM_PROCESSADO  ||
                        $paymentApi->status_detail == MercadoPagoApiService::EM_ANALISE
                    ) {
                        $valores->valorPendente += $paymentApi->additional_info->items[0]->unit_price * $quantidade;
                        continue;
                    };

                    $dtNow      = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
                    $dtCreation = new DateTime($payment->dt_updated, new DateTimeZone('America/Sao_Paulo'));
                    $horas      = $dtCreation->diff($dtNow)->h;
                    if ($horas >= 1) {
                        $api->cancelaPagamento($payment->payment_id);
                        $payment->status = MercadoPagoApiService::CANCELADO;
                        $payment->save();
                    } else {
                        $valores->valorPendente += $paymentApi->additional_info->items[0]->unit_price * $quantidade;
                    }
                }
            }
        }

        return $valores;
    }

    private function verificaPagamentosManuais(): float {
        /** @var PaymentManual[] */
        $paymentsManuais = PaymentManual::where('presente_id', '=', $this->id)->get();

        $valorPago = 0;
        foreach ($paymentsManuais as $payment) {
            $valorPago += $payment->valor;
        }

        return $valorPago;
    }

    private function unsetCota() {
        if (isset($this->cotas))                unset($this->cotas);
        if (isset($this->cotas_disponiveis))    unset($this->cotas_disponiveis);
        if (isset($this->vlr_cota))             unset($this->vlr_cota);
    }

    public function setCota() {
        if($this->valor < self::vlrMinParcelaCota * 2 || abs($this->valor - $this->vlr_presenteado - $this->vlr_processando) < 0.3) return;

        $valorPendente              = $this->valor - $this->vlr_presenteado - $this->vlr_processando;
        $this->cotas                = round($this->valor / self::vlrMinParcelaCota);
        $this->cotas_disponiveis    = round($valorPendente / self::vlrMinParcelaCota);
        $this->vlr_cota             = $valorPendente / $this->cotas_disponiveis;
    }
}
