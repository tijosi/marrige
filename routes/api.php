<?php

use App\Enums\TEnum;
use App\Http\Controllers\PadrinhosController;
use App\Http\Controllers\PresentesController;
use App\Models\User;
use App\Models\WebhookPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


    Route::any('/padrinhos', [PadrinhosController::class, 'handle']);
});

Route::get('/', function() {
    return response()->json([
        'status' => 'success',
        'data' => 'REST API Funcionando!'
    ]);
});

Route::get('/enum/{enumClass}', [TEnum::class, 'getAllProperties']);
Route::any('/webhook_payment', [WebhookPayment::class, 'salvarWebhook']);
Route::any('/login', function (Request $request) {
    $telefone = $request->telefone;
    $user = User::where('telefone', '=', $telefone)->first();

    if (empty($user)) {
        throw new Exception('Convidado não encontrado, por favor revise o número de telefone');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});
