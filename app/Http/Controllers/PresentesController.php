<?php

namespace App\Http\Controllers;

use App\Models\Presente;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

            default:
                throw new Exception('Método de Requisição não indentificado');
                break;
        }

    }

    private function listAll() {

        $presentes = Presente::all();

        foreach ($presentes as $key) {
            $key->path = asset('img/presentes/' . $key->name_img);
            $key->valor = ($key->valor_min + $key->valor_max)/2;
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
        $data['vlr_minimo'] = $data['vlr_minimo'] ?? 0;
        $data['vlr_maximo'] = $data['vlr_maximo'] ?? 0;
        $media = ($data['vlr_minimo'] + $data['vlr_maximo'])/2;

        foreach (self::LEVEL as $level) {
            if ($media > $level['min_valor']) {
                $level = $level['descricao'];
                break;
            }
        }

        $record = new Presente();
        $record->nome               = $data['nome_presente'];
        $record->valor_min          = $data['vlr_minimo'];
        $record->valor_max          = $data['vlr_maximo'];
        $record->level              = $level;
        $record->name_img           = $imageName;
        $record->img_url            = $data['link'];
        $record->flg_disponivel     = 1;
        $record->save();

        $image->move(public_path('img/presentes'), $imageName);


        return $record;

    }

    public function confirmPresente(Request $request) {

        $record = Presente::find($request->id);

        if ($record->flg_disponivel != 1) throw new Exception('Item já foi Selecionado');

        $user = User::find(Auth::user()->id);

        $record->flg_disponivel     = 0;
        $record->name_selected      = $user->name;
        $record->selected_at        = $this->toMySQL('now', true);
        $record->save();

        // $historico = new Historico();
        // $historico->title       = 'Presente Selecionado';
        // $historico->user_name   = $user->name;
        // $historico->body        = 'Confirmou o presente <b>' .  $request->nome . '</b>. Vamos Comemorar!';
        // $historico->created_at  = $this->toMySQL('now', true);
        // $historico->save();

        // return $historico;

        return TRUE;
    }

    public static function toMySQL($date, $time = FALSE, $fromTimeZone = 'UTC', $toTimeZone = 'America/Sao_Paulo') {
        if (empty(trim($date))) return NULL;
        $format = $time ? self::MYSQL_DATETIME_FORMAT : self::MYSQL_DATE_FORMAT;

        $dt = new DateTime($date, new DateTimeZone($fromTimeZone));

        $dt->setTimezone(new DateTimeZone($toTimeZone));

        return $dt->format($format);
    }
}
