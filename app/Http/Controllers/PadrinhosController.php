<?php

namespace App\Http\Controllers;

use App\helpers\Helper;
use App\Http\Api\MercadoPagoApiService;
use App\Models\GiftPayment;
use App\Models\Presente;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            case 'POST':
                return $this->save($request);
                break;

            case 'DELETE':
                return $this->delete($request);
                break;

            default:
                throw new Exception('Método da Requisição não está programado para ser procesado');
                break;
        }

    }

    private function getPresente($id) {
        $presente = Presente::where('id', '=', $id)->first();

        if (empty($presente)) {
            throw new Exception('Presente Não encontrado');
        }
        $presente = Presente::verificaPresente($presente);
        $presente->valor = ($presente->valor_min + $presente->valor_max)/2;
        $presente->tags = json_decode($presente->tags);

        return $presente->toArray();
    }

    private function save( Request $request ) {
        $data = $request->input();
        $image = $request->file('file');

        if (!in_array($image->extension(), ['jpg', 'png', 'jpeg', 'eps', 'psd'])) {
            throw new Exception('O Arquivo não é uma imagem');
        }

        $imageName = time() . '_' . str_replace(' ', '_', $request['nome_presente']) . '.' . $image->getClientOriginalExtension();
        $path = 'img/presentes/';

        $uploadImg = Cloudinary::upload($request->file('file')->getRealPath(),[
            'folder' => $path,
            'public_id' => $imageName,
        ])->getSecurePAth();

        $record = new Presente();
        $record->nome               = $data['nome_presente'];
        $record->valor_min          = $data['vlr_minimo'] ?? 0;
        $record->valor_max          = $data['vlr_maximo'] ?? 0;
        $record->level              = $data['categoria'];
        $record->descricao          = $data['descricao'] ?? null;
        $record->name_img           = $imageName;
        $record->path_img           = $uploadImg;
        $record->img_url            = $data['link'] ?? null;
        $record->tags               = $data['tags'] ?? null;
        $record->flg_disponivel     = 1;
        $record->save();

        return $record;

    }

    private function delete( Request $request ) {
        if (Auth::user()->role_id != 1) {
            throw new Exception('Não Autorizado');
        }

        if (empty($request['presenteId'])) {
            throw new Exception('Por favor, passsar o ID do presente');
        }

        $presente = Presente::verificaPresente(Presente::find($request['presenteId']));

        if (empty($presente)) {
            throw new Exception('Presente não encontrado');
        }

        if (!empty($presente->payment_url)) {
            $payment = GiftPayment::where('url', '=', $presente->payment_url)->first();
            if ($payment->status != MercadoPagoApiService::APROVADO) {
                throw new Exception('Presente está em processo de Pagamento, Consulte seu Noivo :D');
            }
        }

        Cloudinary::destroy('img/presentes/' . $presente->name_img);
        $presente->delete();
        return TRUE;
    }

    public function confirmar(Request $request) {
        if (empty($request['presenteId'])) {
            throw new Exception('Por favor passsar o ID do presente');
        }

        $presente = Presente::find($request['presenteId']);
        if ($presente->flg_disponivel != 1) throw new Exception('Presente já foi Selecionado');

        switch ($request['tipo']) {
            case Presente::VALOR:
                $api = new MercadoPagoApiService();
                $payment = $api->gerarPagamentoPresente($presente);

                return (object) ['link' => $payment];
                break;

            case Presente::PRODUTO:
                $presente->flg_disponivel       = 0;
                $presente->name_selected_id     = Auth::user()->id;
                $presente->selected_at          = Helper::toMySQL('now', true);
                $presente->tipo_selected        = Presente::PRODUTO;
                $presente->save();

                return $presente;
                break;
        }
    }
}
