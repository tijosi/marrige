<?php

namespace App\Http\Controllers;

use App\helpers\Helper;
use App\Models\GiftPayment;
use App\Models\Presente;
use App\Models\Presentes;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class PresentesController extends Controller {

    const LEVEL = [
        ['min_valor' => 751,    'descricao' => 'ALTO'],
        ['min_valor' => 201,    'descricao' => 'MEDIO'],
        ['min_valor' => 1,      'descricao' => 'BAIXO']
    ];

    public function handle( Request $request ) {

        switch ($request->method()) {
            case 'GET':
                return $this->listAll($request);
                break;

            case 'POST':
                return $this->save($request);
                break;

            case 'DELETE':
                return $this->delete($request);
                break;

            default:
                throw new Exception('Método de Requisição não indentificado');
                break;
        }

    }

    private function listAll() {
        $presentes = Presente::all();
        foreach ($presentes as $presente) {
            $presente = Presente::verificaPresente($presente);
            $presente->path = $presente->path_img;
            $presente->valor = ($presente->valor_min + $presente->valor_max)/2;
        }
        return $presentes->toArray();
    }

    private function save( Request $request ) {

        $data = $request->input();
        $image = $request->file('file');

        if (!in_array($image->extension(), ['jpg', 'png', 'jpeg', 'eps', 'psd'])) {
            throw new Exception('O Arquivo não é uma imagem');
        }

        $imageName = time() . '_' . str_replace(' ', '_', $request['nome_presente']) . '.' . $image->getClientOriginalExtension();
        $path = 'img/presentes/';

        $data['vlr_minimo'] = $data['vlr_minimo'] ?? 0;
        $data['vlr_maximo'] = $data['vlr_maximo'] ?? 0;
        $media = ($data['vlr_minimo'] + $data['vlr_maximo'])/2;

        foreach (self::LEVEL as $level) {
            if ($media > $level['min_valor']) {
                $level = $level['descricao'];
                break;
            }
        }

        $uploadImg = Cloudinary::upload($request->file('file')->getRealPath(),[
            'folder' => $path,
            'public_id' => $imageName,
        ])->getSecurePAth();

        $record = new Presente();
        $record->nome               = $data['nome_presente'];
        $record->valor_min          = $data['vlr_minimo'];
        $record->valor_max          = $data['vlr_maximo'];
        $record->level              = $data['categoria'];
        $record->name_img           = $imageName;
        $record->path_img           = $uploadImg;
        $record->img_url            = $data['link'] ?? null;
        $record->flg_disponivel     = 1;
        $record->save();

        return $record;

    }

    private function delete( Request $request ) {
        if (Auth::user()->role_id != 1) {
            throw new Exception('Não Autorizado');
        }

        if (empty($request['presenteId'])) {
            throw new Exception('Por favor passsar o ID do presente');
        }

        $presente = Presente::verificaPresente(Presente::find($request['presenteId']));

        if (!empty($presente->payment_url)) {
            throw new Exception('Presente está em processo de Pagamento, Consulte seu Noivo :D');
        }

        if (empty($presente)) {
            throw new Exception('Presente não encontrado');
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
            case 'Valor':
                $payment = GiftPayment::gerarPagamentoPresente($presente);
                break;

            case 'Presente':
                $presente->flg_disponivel       = 0;
                $presente->name_selected_id     = Auth::user()->id;
                $presente->selected_at          = Helper::toMySQL('now', true);
                $presente->tipo_selected        = $request['tipo'] == 'Valor' ? Presente::VALOR : Presente::PRESENTE;
                $presente->save();
                break;
        }

        // $historico = new Historico();
        // $historico->title       = 'Presente Selecionado';
        // $historico->user_name   = $user->name;
        // $historico->body        = 'Confirmou o presente <b>' .  $request->nome . '</b>. Vamos Comemorar!';
        // $historico->created_at  = Helper::toMySQL('now', true);
        // $historico->save();

        // return $historico;

        return $request['tipo'] == 'Valor' ? (object) [ 'link' => $payment] : $presente;
    }
}
