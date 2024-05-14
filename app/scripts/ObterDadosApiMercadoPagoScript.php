<?php

class ObterDadosApiMercadoPagoScript {

    public function handle() {

        $url = 'https://api.mercadopago.com/v1/payments/search?criteria=desc';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer APP_USR-8480088043089622-051113-6de018eb795661ea415c36811daac4f7-765193147'
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        $status = array_map(function ($line){
            return $line->status;
        } , $data->results);

        var_dump(array_unique($status));

    }
}

$script = new ObterDadosApiMercadoPagoScript();
$script->handle();

