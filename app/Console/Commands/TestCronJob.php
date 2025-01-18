<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TestCronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Логируем выполнение команды
        Log::info('Тестовая задача выполнена');

        // Запускаем обработчик очередей
        Artisan::call('queue:work', [
            '--once' => true,  // Обработать только одну задачу из очереди
        ]);
    }
}
