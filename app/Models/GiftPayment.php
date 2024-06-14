<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class GiftPayment extends Model
{

    const APROVADO = 'approved';
    const CANCELADO = 'cancelled';
    CONST PENDENTE = 'pending';
    CONST EM_PROGRESSO = 'in_process';

    CONST PENDENTE_TRANSFERENCIA = "pending_waiting_transfer";
    CONST PAGAMENTO_EM_PROCESSADO = "pending_contingency";
    CONST EM_ANALISE = "pending_review_manual";

    CONST TOKEN = 'APP_USR-8480088043089622-051113-6de018eb795661ea415c36811daac4f7-765193147';

    protected $table = 'gift_payment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'presente_id',
        'valor',
        'status',
        'url',
        'dt_created',
        'dt_updated',
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

    public static function buscarPagamento($id) {
        if (empty($id)) return;

        $url = "https://api.mercadopago.com/v1/payments/{$id}";
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . self::TOKEN])->get($url);

        if ($response->successful()) {
            $data = json_decode($response->body());
        } else {
            throw new Exception('Erro ao buscar o pagamento: ' . $response->body());
        }

        return $data;
    }

    public static function cancelaPagamento($id) {
        if (empty($id)) return;

        $url = "https://api.mercadopago.com/v1/payments/{$id}";
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . self::TOKEN,])
            ->put($url, ['status' => self::CANCELADO]);

        return json_decode($response->body())->status == self::CANCELADO;
    }

    public static function gerarPagamentoPresente(Presente $presente) {
        $presente = Presente::verificaPresente($presente);

        if (!empty($presente->payment_url)) {
            throw new Exception('Presente está em processo de Pagamento, Consulte o Noivo');
        }

        MercadoPagoConfig::setAccessToken(self::TOKEN);

        $item = [
            "id" => $presente->id,
            "title" => $presente->nome,
            "description" => $presente->nome,
            "currency_id" => "BRL",
            "quantity" => 1,
            "unit_price" => ($presente->valor_min + $presente->valor_max) / 2
        ];

        $payer = [
            "name" => Auth::user()->id . ' - ' . explode(' ', Auth::user()->name)[0],
            "surname" => explode(' ', Auth::user()->name)[1],
            "phone" => [
                "area_code" => substr(Auth::user()->telefone, 0, 2),
                "number" => substr(Auth::user()->telefone, 2)
            ],
        ];

        $request = self::createPreferenceRequest([$item], $payer, $presente);

        try {
            $client = new PreferenceClient();
            $preference = $client->create($request);
            return $preference->init_point;
        } catch (MPApiException $error) {
            return null;
        }
    }

    public static function createPreferenceRequest($items, $payer, $presente) {
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
            "notification_url" => "https://marrige-back-a7da80137ead.herokuapp.com/api/webhook_payment",
            "auto_return" => 'approved',
        ];

        return $request;
    }
}
