<?php

namespace App\helpers;

use DateTime;
use DateTimeZone;

class Helper {

    const MYSQL_DATE_FORMAT = 'Y-m-d';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function toMySQL($date, $time = FALSE, $fromTimeZone = 'UTC', $toTimeZone = 'America/Sao_Paulo') {
        if (empty(trim($date))) return NULL;
        $format = $time ? self::MYSQL_DATETIME_FORMAT : self::MYSQL_DATE_FORMAT;

        $dt = new DateTime($date, new DateTimeZone($fromTimeZone));

        $dt->setTimezone(new DateTimeZone($toTimeZone));

        return $dt->format($format);
    }
}
