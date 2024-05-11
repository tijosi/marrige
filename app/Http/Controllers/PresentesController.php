<?php

namespace App\Http\Controllers;

use App\Models\Presente;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresentesController extends Controller {

    const MYSQL_DATE_FORMAT = 'Y-m-d';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

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

        $presente = Presente::find($request['presenteId']);

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

        $presente->flg_disponivel       = 0;
        $presente->name_selected_id     = Auth::user()->id;
        $presente->selected_at          = $this->toMySQL('now', true);
        $presente->save();

        // $historico = new Historico();
        // $historico->title       = 'Presente Selecionado';
        // $historico->user_name   = $user->name;
        // $historico->body        = 'Confirmou o presente <b>' .  $request->nome . '</b>. Vamos Comemorar!';
        // $historico->created_at  = $this->toMySQL('now', true);
        // $historico->save();

        // return $historico;

        return $presente;
    }

    public static function toMySQL($date, $time = FALSE, $fromTimeZone = 'UTC', $toTimeZone = 'America/Sao_Paulo') {
        if (empty(trim($date))) return NULL;
        $format = $time ? self::MYSQL_DATETIME_FORMAT : self::MYSQL_DATE_FORMAT;

        $dt = new DateTime($date, new DateTimeZone($fromTimeZone));

        $dt->setTimezone(new DateTimeZone($toTimeZone));

        return $dt->format($format);
    }
}
