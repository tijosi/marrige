<?php

namespace App\Enums;

enum ChaPanelaEnum {
    case COZINHA;
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
            self::GERAL         => 'GERAL'
        };

    }

    private function getColor(): string {

        return match ($this) {
            self::COZINHA       => '#86BA90',
            self::GERAL         => '#dfa06e'
        };

    }
}
