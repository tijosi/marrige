<?php

namespace App\Http\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExceptionApiSerivce {};

class ApiService {

    /** @var string */
    protected $token;

    /** @var string */
    protected $url;

    /** @var array */
    private $headers;

    public function __construct() {}

    // CONFIGURAÇÃO E VALIDAÇÃO:
    private function isAuthenticate(): bool {
        if (empty($this->token) || $this->headers['Authorization']) {
            return FALSE;
        }

        return TRUE;
    }

    protected function auth() {}

    protected function setHeaderGlobal(string $nome, string $valor): void {
        $this->headers[] = [$nome => $valor];
    }

    protected function setAuthorization() {
        $this->setHeaderGlobal('Authorization', "Bearer $this->token");
    }

    protected function validateRequest(Response $response) {
        if ($response->successful()) {
            return json_decode($response->body());
        } else {
            throw new ExceptionApiSerivce('Erro na validação da requisição: ' . $response->body());
        }
    }


    // REQUISIÇÕES:
    protected function get(string $url, array $body = null, array $header = []): mixed {
        if (!$this->isAuthenticate()) $this->auth();

        $response = Http::withHeaders(array_merge($this->headers, $header))->get($url, $body);
        return $this->validateRequest($response);
    }

    protected function post(string $url, array $body = [], array $header = []): mixed {
        if (!$this->isAuthenticate()) $this->auth();

        $response = Http::withHeaders(array_merge($this->headers, $header))->post($url, $body);
        return $this->validateRequest($response);
    }

    protected function put(string $url, array $body = [], array $header = []): mixed {
        if (!$this->isAuthenticate()) $this->auth();

        $response = Http::withHeaders(array_merge($this->headers, $header))->put($url, $body);
        return $this->validateRequest($response);
    }

    protected function delete(string $url, array $body = [], array $header = []): mixed {
        if (!$this->isAuthenticate()) $this->auth();

        $response = Http::withHeaders(array_merge($this->headers, $header))->delete($url, $body);
        return $this->validateRequest($response);
    }

}
