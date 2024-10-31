<?php


namespace App\Services;

use Carbon\Carbon;


class DateFormatService
{
    public function formatCustomDate($dateString)
    {
        // Преобразуем строку в объект Carbon
        $date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $dateString, 'UTC');
        // Форматируем дату в нужный формат
        return $date->format('d.m.y');
    }
}
