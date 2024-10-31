<?php


namespace App\Services\Date;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DateFilterService
{
    public function filterCostPricesByDateRange(array $costPrices, $startDate, $endDate)
    {
        $filtered = [];

        // Преобразуем даты начала и конца в формат d.m.y
        $start = $this->normalizeDate($startDate);
        $end = $this->normalizeDate($endDate);

        // Преобразуем даты начала и конца в объекты Carbon для сравнения
        $startCarbon = Carbon::createFromFormat('d.m.y', $start);
        $endCarbon = Carbon::createFromFormat('d.m.y', $end);

        foreach ($costPrices as $date => $price) {
            Log::info('$date: '. $date);

            // Преобразуем текущую дату в формат d.m.y
            $currentDate = $this->normalizeDate($date);
            Log::info('$currentDate: '. $currentDate);

            // Преобразуем текущую дату в объект Carbon для сравнения
            $currentCarbon = Carbon::createFromFormat('d.m.y', $currentDate);

            Log::info('$currentCarbon: '. $currentCarbon);

            if ($currentCarbon->between($startCarbon, $endCarbon)) {
                $filtered[$currentDate] = $price;
            }

            Log::info('$filtered: '. json_encode($filtered));
        }

        return $filtered;
    }

    private function normalizeDate($date)
    {
        // Разделяем дату на части
        $parts = explode('.', $date);

        // Проверяем, что у нас есть три части (день, месяц, год)
        if (count($parts) === 3) {
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];

            // Определяем формат даты
            if ($month > 12) {
                // Если месяц больше 12, то формат m.d.Y
                $format = 'm.d.Y';
            } elseif ($day > 12) {
                // Если день больше 12, то формат d.m.Y
                $format = 'd.m.Y';
            } else {
                // Если оба числа меньше или равны 12, формат неоднозначен
                // Можно использовать дополнительные критерии или запросить у пользователя
                $format = 'd.m.Y'; // По умолчанию используем d.m.Y
            }

            // Преобразуем дату в нужный формат
            $parsedDate = Carbon::createFromFormat($format, $date);
            return $parsedDate->format('d.m.y'); // Преобразуем в нужный формат
        }

        return null; // Если формат не удалось определить
    }
}
