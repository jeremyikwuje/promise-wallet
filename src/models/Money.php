<?php

class Money
{
    public static $decimals = 8;

    public static function format(
        float $amount,
        int $decimals = 8,
        string $decimals_seperator = '.',
        string $thousand_seperator = ','
    ): string
    {
        if (1 <= $amount || 0 >= $amount) {
            $decimals = 2;
        }

        $amount = self::round($amount, $decimals);

        return number_format($amount, $decimals, $decimals_seperator, $thousand_seperator);
    }

    public static function round(
        string $amount,
        int $decimals = 8,
        int $roundMode = PHP_ROUND_HALF_UP
    ): string {
        if (0 >= $amount) {
            $decimals = 2;
        }

        $power = pow(10, $decimals);
        $round = round($amount * $power) / $power;

        return rtrim(rtrim(sprintf("%.{$decimals}F", $round), '0'), ".");  
    }

    public static function plus(array $values, int $decimals = 0): string
    {
        if ($decimals === 0) {
            $decimals = self::$decimals;
        }

        $power = pow(10, $decimals);
        $sum = 0;

        foreach($values as $value) {
            $sum = $sum + (floatval($value) * $power);
        }

        return self::round($sum / $power, $decimals);
    }

    public static function minus(array $values, int $decimals = 0): string
    {
        if ($decimals === 0) {
            $decimals = self::decimals;
        }

        $power = pow(10, $decimals);
        $sum = floatval($values[0]) * $power;

        unset($values[0]);

        foreach($values as $value) {
            $sum = $sum - (floatval($value) * $power);
        }

        return self::round($sum / $power, $decimals);
    }

    public static function multiply(array $values, int $decimals = 0): string
    {
        if ($decimals === 0) {
            $decimals = self::$decimals;
        }

        $power = pow(10, $decimals);
        $sum = 1;

        foreach($values as $value) {
            $sum = $sum * (floatval($value) * $power);
        }

        // important we also power the denominator by 2
        // to avoid large inacurrate value
        return self::round($sum / pow($power, count($values)), $decimals); 
    }

    public static function divide(array $values, int $decimals = 0): string
    {
        if ($decimals === 0) {
            $decimals = self::$decimals;
        }

        $power = pow(10, $decimals);
        $sum = floatval($values[0]);

        unset($values[0]);

        foreach($values as $value) {
            $sum = ($sum * $power) / (floatval($value) * $power);
        }

        // important we also power the denominator by 2
        // to avoid large inacurrate value
        return self::round($sum, $decimals); 
    }

    public static function percentRatio(int $numerator, int $denumerator): float
    {
        $ratio = self::divide([$denumerator, $numerator]);
        $ratio = self::multiply([$ratio, 100]);
    
        return $ratio;
    }

    public static function percentDifference(int $numerator, int $denumerator): float
    {
        if ($denumerator == 0) {
            return 0;
        }

        if ($numerator == 0) {
            return 0;
        }

        $ratio = self::percentRatio($numerator, $denumerator);
        $diff = $ratio - 100;
    
        return round($diff, 8);
    }

    static function cent(float $amount): int {
        return $amount * 100;
    }

    static function dollar(int $amount): float {
        return round($amount / 100, 2, PHP_ROUND_HALF_EVEN);
    }
}