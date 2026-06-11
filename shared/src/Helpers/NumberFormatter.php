<?php

namespace Shared\Helpers;

class NumberFormatter
{
    public static function cascadingRound($value)
    {
        $numStr = number_format($value, 3, '.', '');
        
        $parts = explode('.', $numStr);
        $wholePart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';
        
        if (!$decimalPart) return (int)$wholePart;
        
        $digits = str_split($decimalPart);
        $digits = array_map('intval', $digits);
        
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            if ($i < count($digits) - 1 && $digits[$i + 1] >= 5) {
                $digits[$i]++;
            }
            
            if ($digits[$i] === 10) {
                $digits[$i] = 0;
                if ($i > 0) {
                    $digits[$i - 1]++;
                } else {
                    $wholePart = (int)$wholePart + 1;
                }
            }
        }
        
        if ($digits[0] >= 5) {
            $wholePart = (int)$wholePart + 1;
        }
        
        return (int)$wholePart;
    }

    public static function roundToTwoDecimals($value)
    {
        return round($value, 2);
    }

    public static function formatTwoDecimals($value, $decimalSeparator = '.', $thousandsSeparator = ',')
    {
        $rounded = self::roundToTwoDecimals($value);
        return number_format($rounded, 2, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatCurrency($value)
    {
        return self::formatTwoDecimals($value, ',', '.');
    }

    public static function formatForDatabase($value)
    {
        return self::roundToTwoDecimals($value);
    }

    public static function calculatePercentageDiscount($originalValue, $discountPercent)
    {
        $discountAmount = $originalValue * ($discountPercent / 100);
        $discountedValue = $originalValue - $discountAmount;
        return self::roundToTwoDecimals($discountedValue);
    }

    public static function percentageOf($value, $percent)
    {
        $amount = (float)$value * ((float)$percent / 100.0);
        return self::roundToTwoDecimals($amount);
    }

    public static function calculateNominalDiscount($originalValue, $discountAmount)
    {
        $discountedValue = $originalValue - $discountAmount;
        return self::roundToTwoDecimals($discountedValue);
    }

    public static function calculateSubtotal($unitPrice, $quantity)
    {
        $subtotal = $unitPrice * $quantity;
        return self::roundToTwoDecimals($subtotal);
    }

    public static function roundToWholeNumber($value)
    {
        return round($value);
    }

    public static function formatInvoiceAmount($value)
    {
        $rounded = self::roundToWholeNumber($value);
        return number_format($rounded, 0, ',', '.');
    }

    public static function parseNumericValue($value)
    {
        if (empty($value) || $value === null) {
            return 0;
        }
        
        $value = (string)$value;
        
        $cleaned = preg_replace('/[^\d,.-]/', '', $value);
        
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        }
        elseif (preg_match('/^\d{1,3}(,\d{3})*\.\d{2}$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        else {
            $lastDot = strrpos($cleaned, '.');
            $lastComma = strrpos($cleaned, ',');
            
            if ($lastDot !== false && $lastComma !== false) {
                if ($lastDot > $lastComma) {
                    $cleaned = str_replace(',', '', $cleaned);
                } else {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            } elseif ($lastComma !== false) {
                if (strlen($cleaned) - $lastComma <= 3) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }
            }
        }
        
        $numericValue = (float)$cleaned;
        
        return $numericValue;
    }

    public static function calculateDPP($totalAmount)
    {
        return self::roundToWholeNumber($totalAmount);
    }

    public static function calculateDPP1112($dpp)
    {
        $dpp1112 = $dpp * (11/12);
        return self::roundToWholeNumber($dpp1112);
    }

    public static function calculatePPN($dpp1112)
    {
        $ppn = $dpp1112 * 0.12;
        return self::roundToWholeNumber($ppn);
    }

    public static function calculateGrandTotal($dpp, $ppn)
    {
        $grandTotal = $dpp + $ppn;
        return self::roundToWholeNumber($grandTotal);
    }

    public static function formatDecimal($value)
    {
        return self::roundToTwoDecimals((float)$value);
    }

    public static function multiplyDecimal($a, $b)
    {
        return self::roundToTwoDecimals((float)$a * (float)$b);
    }

    public static function subtractDecimal($a, $b)
    {
        return self::roundToTwoDecimals((float)$a - (float)$b);
    }
}
