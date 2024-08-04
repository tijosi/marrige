<?php

namespace App\Http\Controllers;

use App\helpers\Helper;
use App\Http\Api\MercadoPagoApiService;
use App\Models\GiftPayment;
use App\Models\PaymentManual;
use App\Models\Presente;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PresentesController extends Controller {

    public function handle( Request $request ) {
        switch ($request->method()) {
            case 'GET':
                if (isset($request->id)) {
                    return $this->getPresente($request->id);
                } else {
                    return $this->listAll($request);
                }
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
        $presente->verificaPresente();
        $presente->setCota();
        $presente->tags = json_decode($presente->tags);

        return $presente->toArray();
    }

    private function listAll() {
        $presentes = Presente::all();
        foreach ($presentes as $presente) {
            $presente->verificaPresente();
            $presente->setCota();
            $presente->tags = json_decode($presente->tags);
        }
        return $presentes->toArray();
    }

    private function save( Request $request ) {
        $request->validate([
            'nome_presente'     => 'required | string',
            'categoria'         => 'required',
        ]);

        $data = $request->input();

        $image = $request->file('file');

        if (empty($image)) {
            throw new Exception('Imagem não encontrada');
        }

        if (!in_array($image->extension(), ['jpg', 'png', 'jpeg', 'eps', 'psd'])) {
            throw new Exception('O Arquivo não é uma imagem');
        }

        $imageName = time() . '_' . str_replace(' ', '_', $request['nome_presente']) . '.' . $image->getClientOriginalExtension();
        $path = 'img/presentes/';

        $uploadImg = Cloudinary::upload($request->file('file')->getRealPath(),[
            'folder' => $path,
            'public_id' => $imageName,
        ])->getSecurePAth();

        if (empty($data['prioridade'])) {
            $registros = DB::table('presentes')->count();
        }

        $data['vlrSimbolico'] = $data['vlrSimbolico'] ?? 0;

        $record = new Presente();
        $record->nome               = $data['nome_presente'];
        $record->valor              = $data['valor'] ?? 0;
        $record->level              = $data['categoria'];
        $record->descricao          = $data['descricao'] ?? null;
        $record->name_img           = $imageName;
        $record->path_img           = $uploadImg;
        $record->img_url            = $data['link'] ?? null;
        $record->tags               = $data['tags'] ?? null;
        $record->vlr_simbolico      = $data['vlrSimbolico'] ? 1 : 0;
        $record->prioridade         = $data['prioridade'] ?? $registros + 1;
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

        $presente = Presente::find($request['presenteId']);
        $presente->verificaPresente();

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

        /** @var Presente */
        $presente = Presente::find($request['presenteId']);
        if ($presente->flg_disponivel == 0) throw new Exception('Presente já foi Selecionado');

        switch ($request['tipo']) {
            case Presente::VALOR:
                $api = new MercadoPagoApiService();
                $presente->verificaPresente();
                $valor = $presente->valor - $presente->vlr_presenteado - $presente->vlr_processando;
                $payment = $api->gerarPagamentoPresente($presente, $valor);

                return (object) ['link' => $payment];
                break;

            case Presente::COTA:
                $api = new MercadoPagoApiService();
                $presente->setCota();
                $payment = $api->gerarPagamentoPresente($presente, $presente->vlr_cota, $request['qtd_cota']);

                return (object) ['link' => $payment];
                break;

            case Presente::PRODUTO:
                $presente->flg_disponivel       = 0;
                $presente->selected_by_user_id  = Auth::user()->id;
                $presente->selected_at          = Helper::toMySQL('now', true);
                $presente->save();

                return $presente;
                break;
        }
    }

    public function confirmarPagamentoManual(Request $request) {
        if (Auth::user()->role_id != 1) {
            throw new Exception('Permissão Negada');
        }

        $paymentManual = new PaymentManual();
        $paymentManual->user_id     = $request['pagante'];
        $paymentManual->valor       = $request['valor'];
        $paymentManual->presente_id = $request['presente_id'];
        $paymentManual->status      = 'PAGO';
        $paymentManual->save();

        return $paymentManual;
    }

    public function cancelarSelecao(Request $request) {
        if (!isset($request['presenteId'])) {
            throw new Exception('Presente Id não encontrado');
        }

        $presente = Presente::find($request['presenteId']);

        if (empty($presente->selected_by_user_id)) {
            throw new Exception('Presente não está selecionado');
        }

        if (Auth::user()->id != $presente->selected_by_user_id && Auth::user()->role_id != 1) {
            throw new Exception('Permissão Negada');
        }

        $presente->flg_disponivel      = 1;
        $presente->selected_by_user_id = null;
        $presente->selected_at         = null;
        $presente->save();

        return $presente;
    }
}
