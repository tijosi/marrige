<?php

namespace App\Enums;

require __DIR__.'/../../vendor/autoload.php';

class TEnum {

    public static function getAllProperties(string $enumClass): array {

        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $enumClass)));
        $caminho = 'App\\Enums\\' . $class;
        $properties = [];

        foreach ($caminho::cases() as $case) {
            $properties[] = $case->getProperties();
        }

        return $properties;

    }

}
