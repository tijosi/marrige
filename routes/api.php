<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/autenticacao', function (Request $request) {
    $user = $request->user();

    return $user;
});

Route::get('/', function() {

    return response()->json([
        'status' => 'success',
        'data' => 'API Funcionando!'
    ]);

});

Route::any('/login', function (Request $request) {

    $telefone = $request->telefone;
    $user = User::where('telefone', '=', $telefone)->first();

    if (empty($user)) {
        throw new Exception('Convidado não encontrado, revise o número');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});
