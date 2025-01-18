<?php

// Генерация случайного имени для файла
$randomFilename = 'test_' . uniqid() . '.txt';

// Путь к файлу
$filePath = __DIR__ . '/' . $randomFilename;

// Открытие файла для записи
$file = fopen($filePath, 'w');

// Запись в файл
if ($file) {
    fwrite($file, "Скрипт выполнен успешно: " . date('Y-m-d H:i:s') . PHP_EOL);
    fclose($file);
    echo "Файл успешно создан: $filePath" . PHP_EOL;
} else {
    echo "Ошибка при создании файла" . PHP_EOL;
}
