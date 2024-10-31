<?php


namespace App\Helper;


use PhpOffice\PhpSpreadsheet\Cell\Cell;

class AppDefaultValueBinder extends \Maatwebsite\Excel\DefaultValueBinder {
    /**
     * @throws \JsonException
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_array($value)) {
            $value = \json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return parent::bindValue($cell, $value);
    }
}
