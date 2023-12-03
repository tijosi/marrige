<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function() {

    return response()->json([
        'status' => 'success',
        'data' => 'API Funcionando!'
    ]);

});

Route::post('/login', function (Request $request) {

    $telefone = $request->telefone;
    $user = DB::table('users')->where('telefone', '=', $telefone)->first();

    if (empty($user)) {
        throw new Exception('Convidado não encontrado, revise o número');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});
