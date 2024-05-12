<?php

use App\Enums\TEnum;
use App\Http\Controllers\PresentesController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::any('/payment', function(Request $request) {
    $accessToken = 'APP_USR-8480088043089622-051113-6de018eb795661ea415c36811daac4f7-765193147';

    $paymentId = '77911261241';

    $url = "https://api.mercadopago.com/v1/payments/$paymentId";

    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    );

    $data = array(
        "status" => "cancelled" // Define o status do pagamento como cancelado
    );

    $ch = curl_init();

    // Configura as opções da requisição cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    return $response;

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
