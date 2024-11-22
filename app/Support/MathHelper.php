<?php


namespace App\Support;


use BCMathExtended\BC;
use Illuminate\Support\Facades\Log;

class MathHelper
{
    public static function saveDiv($numerator, $denominator, $scale = 2){
        if (BC::comp($numerator, 0, $scale) === 0){
            return '0.00';
        }

        if (BC::comp($denominator, 0, $scale) === 0){
            Log::warning('Attempted division by zero');
            return '0.00';
        }

        return BC::div($numerator, $denominator, $scale);
    }
}
