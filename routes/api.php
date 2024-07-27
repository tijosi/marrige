<?php

use App\Enums\TEnum;
use App\helpers\Helper;
use App\Http\Controllers\PadrinhosController;
use App\Http\Controllers\PresentesController;
use App\Models\User;
use App\Models\WebhookPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/autenticacao', function(Request $request) {
        return $request->user();
    });

    Route::get('/database/usuarios', function(Request $request) {
        if (Auth::user()->id != 1) {
            throw new Exception('Permissão Negada');
        }

        return User::all()->toArray();
    });

    Route::get('/admin', function(Request $request) {
        $user = $request->user();
        return $user->role_id == 1;
    });

    Route::any('/presentes',                                [PresentesController::class, 'handle']);
    Route::post('/presentes/confirmar',                     [PresentesController::class, 'confirmar']);
    Route::post('/presentes/adicionar-pagamento-manual',    [PresentesController::class, 'confirmarPagamentoManual']);


    Route::any('/padrinhos', [PadrinhosController::class, 'handle']);
});

Route::get('/', function() {
    return response()->json([
        'status' => 'success',
        'data' => 'REST API Funcionando!'
    ]);
});

Route::get('/enum/{enumClass}',     [TEnum::class, 'getAllProperties']);
Route::any('/webhook_payment',      [WebhookPayment::class, 'salvarWebhook']);

Route::any('/login', function (Request $request) {
    $telefone = $request->telefone;
    $user = User::where('telefone', '=', $telefone)->first();

    if (empty($user)) {
        throw new Exception('Convidado não encontrado, por favor revise o número de telefone');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});

Route::post('/create-user',         function(Request $request) {
    $data = $request->input();

    $data['id'] ? $user     = User::find($data['id']) : $user = new User();
    $user->name             = $data['name'];
    $user->telefone         = $data['telefone'];
    $user->imagem           = time() . '_' . explode(' ', $data['name'])[0] . '_' . explode(' ', $data['name'])[1];
    $user->role_id          = $data['role_id'];
    if (empty($data['id'])) {
        $user->created_at   = Helper::toMySQL('now', TRUE);
    }
    $user->updated_at       = Helper::toMySQL('now', TRUE);
    $user->save();

    return $user;
});
