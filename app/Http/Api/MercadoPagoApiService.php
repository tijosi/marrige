<?php

namespace App\Http\Api;

use App\Models\Presente;
use Exception;
use Illuminate\Support\Facades\Auth;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoApiService extends ApiService {

    protected $token = 'APP_USR-8480088043089622-051113-6de018eb795661ea415c36811daac4f7-765193147';
    protected $url = 'https://api.mercadopago.com/v1';

    private $notificationUrl = 'https://marrige-back-a7da80137ead.herokuapp.com/api/webhook_payment';

    CONST CANCELADO = 'cancelled';
    CONST APROVADO = 'approved';
    CONST PENDENTE = 'pending';
    CONST EM_PROGRESSO = 'in_process';
    CONST PENDENTE_TRANSFERENCIA = "pending_waiting_transfer";
    CONST PAGAMENTO_EM_PROCESSADO = "pending_contingency";
    CONST EM_ANALISE = "pending_review_manual";

    public function __construct() {}

    protected function auth() {
        $this->setAuthorization();
    }

    public function buscarPagamento($id): object {
        $response = $this->get("/payments/{$id}");
        return $response;
    }

    public function cancelaPagamento($id): object {
        $response = $this->put("/payments/{$id}", ['status' => self::CANCELADO]);
        return $response;
    }

    public function gerarPagamentoPresente(Presente $presente): null|string {
        $presente->verificaPresente();

        if ($presente->flg_disponivel == 0) {
            throw new Exception('Presente está indisponível, Consulte o Noivo');
        }

        if (empty($presente->valor)) {
            $presente->configuraParametros();
        }

        MercadoPagoConfig::setAccessToken($this->token);

        $item = [
            "id" => $presente->id,
            "title" => $presente->nome,
            "description" => $presente->nome,
            "currency_id" => "BRL",
            "quantity" => 1,
            "unit_price" => $presente->valor
        ];

        $payer = [
            "name"      => Auth::user()->id . ' - ' . explode(' ', Auth::user()->name)[0],
            "surname"   => explode(' ', Auth::user()->name)[1],
            "phone"     => [
                "area_code"     => substr(Auth::user()->telefone, 0, 2),
                "number"        => substr(Auth::user()->telefone, 2)
            ],
        ];

        $request = $this->createPreferenceRequest([$item], $payer, $presente);

        try {
            $client = new PreferenceClient();
            $preference = $client->create($request);
            return $preference->init_point;
        } catch (MPApiException $error) {
            throw $error;
        }
    }

    public function createPreferenceRequest($items, $payer, $presente): array {
        $paymentMethods = [
            "excluded_payment_methods" => [],
            "installments" => 12,
            "default_installments" => 1
        ];

        $backUrls = array(
            'success' => 'mercadopago.success',
            'failure' => 'mercadopago.failed'
        );

        $request = [
            "items" => $items,
            "payer" => $payer,
            "payment_methods" => $paymentMethods,
            "back_urls" => $backUrls,
            "statement_descriptor" => "CASA_EDSONSWELEN",
            "external_reference" => $presente->id,
            "expires" => true,
            "notification_url" => $this->notificationUrl,
            "auto_return" => 'approved',
        ];

        return $request;
    }

}
