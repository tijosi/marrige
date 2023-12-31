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
});

Route::get('/enum/{enumClass}', [TEnum::class, 'getAllProperties']);

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
