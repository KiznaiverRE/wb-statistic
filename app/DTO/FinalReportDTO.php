<?php


namespace App\DTO;


class FinalReportDTO
{
    public $advertising_cost = 0;
    public $average_check = 0;
    public $batch_cost = 0;
    public $credited_to_account = 0;
    public $ddr_percent = 0;
    public $fine = 0;
    public $logistic_cost = 0;
    public $logistic_percent = 0;
    public $margin_after_expenses = 0;
    public $orders_count = 0;
    public $profit = 0;
    public $profit_percent = 0;
    public $purchase_cost = 0;
    public $returns_count = 0;
    public $storage_cost = 0;
    public $transfers = 0;

    public function __construct($data = []){
        foreach ($data as $key => $value){
            if (property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    public function toArray(){
        return get_object_vars($this);
    }
}
