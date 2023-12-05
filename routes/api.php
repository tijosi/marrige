<?php

use App\Models\Presente;
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

    Route::get('/presentes', function(Request $request) {

        $presentes = Presente::all();

        $varPontos = 12;

        foreach ($presentes as $key) {
            $key->path = asset('images/presentes/' . $key->name_img);
            $key->valor = ($key->valor_min + $key->valor_max)/2;
            $key->pontos = $key->valor/$varPontos;
        }

        return $presentes->toArray();

    });
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
        throw new Exception('Convidado não encontrado, por favor revise o número de telefone');
    }

    $token = $user->createToken('token-name')->plainTextToken;

    return response()->json(['token' => $token]);
});
