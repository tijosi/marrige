<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\Request;

class PadrinhosController extends Controller {

    public function handle( Request $request ) {
        switch ($request->method()) {
            case 'GET':
                $padrinhos = User::where('role_id', '=', '2')->get();
                foreach ($padrinhos as $padrinho) {
                    if (empty($padrinho->imagem)) {
                        $padrinho->imagem = null;
                    } else {
                        $padrinho->imagem = Cloudinary::getImage('img/padrinhos/' . $padrinho->imagem)->toUrl();
                    }
                }

                return $padrinhos->toArray();
                break;

            default:
                throw new Exception('Método da Requisição não está programado para ser procesado');
                break;
        }

    }
}
