<?php

use App\Enums\TEnum;
use App\Http\Controllers\PresentesController;
use App\Models\User;
use App\Models\WebhookPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\MerchantOrder\Item;
use MercadoPago\Resources\Payment;

// Route::middleware('auth:sanctum')->get('/autenticacao', function (Request $request) {
//     $user = $request->user();
//     return $user;
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/autenticacao', function(Request $request) {
        $user = $request->user();
        return $user;

    });

    Route::get('/admin', function(Request $request) {
        $user = $request->user();
        return $user->role_id == 1;

    });

    Route::any('/presentes', [PresentesController::class, 'handle']);
    Route::post('/presentes/confirmar', [PresentesController::class, 'confirmar']);
});

Route::get('/enum/{enumClass}', [TEnum::class, 'getAllProperties']);

Route::get('/', function() {

    return response()->json([
        'status' => 'success',
        'data' => 'API Funcionando!'
    ]);

});

Route::get('/payment', function() {

    MercadoPagoConfig::setAccessToken('APP_USR-8480088043089622-051113-6de018eb795661ea415c36811daac4f7-765193147');
    MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

    $product1 = array(
        "id" => "1234567890",
        "title" => "Product 1 Title",
        "description" => "Product 1 Description",
        "currency_id" => "BRL",
        "quantity" => 1,
        "unit_price" => 10.0
    );

    $items = array($product1);

    $payer = array(
        "name" => 'Edson',
        "surname" => 'Martins',
        "email" => '64992899016',
    );

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
        "statement_descriptor" => "NAME_DISPLAYED_IN_USER_BILLING",
        "external_reference" => "1234567890",
        "expires" => false,
        "auto_return" => 'approved',
    ];

    // Instantiate a new Preference Client
    $client = new PreferenceClient();

    // Send the request that will create the new preference for user's checkout flow
    $preference = $client->create($request);

    // Useful props you could use from this object is 'init_point' (URL to Checkout Pro) or the 'id'
    return $preference;

});

Route::any('/webhook_payment', function(Request $request) {
    $webhook = new WebhookPayment();
    $webhook->json = json_encode($request->input());
    $webhook->save();
});




Route::any('/login', function (Request $request) {

    $telefone = $request->telefone;
    $user = User::where('telefone', '=', $telefone)->first();

    if (empty($user)) {
        throw new Exception('Convidado não encontrado, por favor revise o número de telefone');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});
