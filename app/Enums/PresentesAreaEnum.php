<?php

namespace App\Enums;

enum PresentesAreaEnum {
    case COZINHA;
    case QUARTO;
    case SALA;
    case SERVICO;
    case ESCRITORIO;
    case GERAL;

    public function getProperties(): array {

        return [
            'id'            => $this->name,
            'descricao'     => $this->getDescricao(),
            'color'         => $this->getColor(),
        ];

    }

    private function getDescricao(): string {

        return match ($this) {
            self::COZINHA       => 'COZINHA',
            self::QUARTO        => 'QUARTO',
            self::SALA          => 'SALA',
            self::SERVICO       => 'SERVIÇO',
            self::ESCRITORIO    => 'ESCRITÓRIO',
            self::GERAL         => 'GERAL'
        };

    }

    private function getColor(): string {

        return match ($this) {
            self::COZINHA       => '#86BA90',
            self::QUARTO        => '#63ADF2',
            self::SALA          => '#4C0827',
            self::SERVICO       => '#B47EB3',
            self::ESCRITORIO    => '#304D6D',
            self::GERAL         => '#dfa06e'
        };

    }
}
