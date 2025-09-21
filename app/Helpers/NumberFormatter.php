<?php

namespace App\Helpers;

class NumberFormatter
{
    /**
     * Apply cascading rounding to a number (rounds each decimal place sequentially)
     * 
     * @param float $value The value to round
     * @return int The rounded whole number
     */
    public static function cascadingRound($value)
    {
        // Convert to string with 3 decimal places
        $numStr = number_format($value, 3, '.', '');
        
        // Split by decimal point
        $parts = explode('.', $numStr);
        $wholePart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';
        
        // If no decimal part, just return the whole number
        if (!$decimalPart) return (int)$wholePart;
        
        // Convert decimal part to array of digits
        $digits = str_split($decimalPart);
        $digits = array_map('intval', $digits);
        
        // Start from right (least significant digit) and work left
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            // Round the current digit
            if ($i < count($digits) - 1 && $digits[$i + 1] >= 5) {
                $digits[$i]++;
            }
            
            // Handle carry
            if ($digits[$i] === 10) {
                $digits[$i] = 0;
                if ($i > 0) {
                    $digits[$i - 1]++;
                } else {
                    // Carry to whole part
                    $wholePart = (int)$wholePart + 1;
                }
            }
        }
        
        // Final step: Apply rounding from first decimal place to whole number
        if ($digits[0] >= 5) {
            $wholePart = (int)$wholePart + 1;
        }
        
        // Return just the whole part (rounded result)
        return (int)$wholePart;
    }

    /**
     * Round a number to 2 decimal places with proper rounding (0.5 rounds up)
     * 
     * @param float $value The value to round
     * @return float The rounded value with 2 decimal places
     */
    public static function roundToTwoDecimals($value)
    {
        return round($value, 2);
    }

    /**
     * Format a number with 2 decimal places and proper rounding
     * 
     * @param float $value The value to format
     * @param string $decimalSeparator The decimal separator (default: '.')
     * @param string $thousandsSeparator The thousands separator (default: ',')
     * @return string The formatted number
     */
    public static function formatTwoDecimals($value, $decimalSeparator = '.', $thousandsSeparator = ',')
    {
        $rounded = self::roundToTwoDecimals($value);
        return number_format($rounded, 2, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a number for display (Indonesian format with 2 decimal places)
     * 
     * @param float $value The value to format
     * @return string The formatted number (e.g., "1.234,56")
     */
    public static function formatCurrency($value)
    {
        return self::formatTwoDecimals($value, ',', '.');
    }

    /**
     * Format a number for database storage (ensure 2 decimal places)
     * 
     * @param float $value The value to format
     * @return float The value rounded to 2 decimal places
     */
    public static function formatForDatabase($value)
    {
        return self::roundToTwoDecimals($value);
    }

    /**
     * Calculate percentage discount with 2 decimal places
     * 
     * @param float $originalValue The original value
     * @param float $discountPercent The discount percentage
     * @return float The discounted value
     */
    public static function calculatePercentageDiscount($originalValue, $discountPercent)
    {
        $discountAmount = $originalValue * ($discountPercent / 100);
        $discountedValue = $originalValue - $discountAmount;
        return self::roundToTwoDecimals($discountedValue);
    }

    /**
     * Calculate percentage of a value with 2-decimal rounding at the final step
     * Avoids premature rounding of (percent/100) that can cause discrepancies
     */
    public static function percentageOf($value, $percent)
    {
        $amount = (float)$value * ((float)$percent / 100.0);
        return self::roundToTwoDecimals($amount);
    }

    /**
     * Calculate nominal discount with 2 decimal places
     * 
     * @param float $originalValue The original value
     * @param float $discountAmount The discount amount
     * @return float The discounted value
     */
    public static function calculateNominalDiscount($originalValue, $discountAmount)
    {
        $discountedValue = $originalValue - $discountAmount;
        return self::roundToTwoDecimals($discountedValue);
    }

    /**
     * Calculate subtotal with quantity and unit price (2 decimal places)
     * 
     * @param float $unitPrice The unit price
     * @param float $quantity The quantity
     * @return float The subtotal
     */
    public static function calculateSubtotal($unitPrice, $quantity)
    {
        $subtotal = $unitPrice * $quantity;
        return self::roundToTwoDecimals($subtotal);
    }

    /**
     * Round a number to whole number (no decimal places)
     * 
     * @param float $value The value to round
     * @return int The rounded whole number
     */
    public static function roundToWholeNumber($value)
    {
        return round($value);
    }

    /**
     * Format a number for invoice display (Indonesian format with no decimal places)
     * 
     * @param float $value The value to format
     * @return string The formatted number (e.g., "1.234")
     */
    public static function formatInvoiceAmount($value)
    {
        $rounded = self::roundToWholeNumber($value);
        return number_format($rounded, 0, ',', '.');
    }

    /**
     * Parse numeric value from various formats including Indonesian currency format
     * Handles formats like "56972,97" (Indonesian) and "56972.97" (US)
     * 
     * @param mixed $value The value to parse
     * @return float The parsed numeric value
     */
    public static function parseNumericValue($value)
    {
        if (empty($value) || $value === null) {
            return 0;
        }
        
        // Convert to string first
        $value = (string)$value;
        
        // Remove currency symbols, spaces, and common separators
        $cleaned = preg_replace('/[^\d,.-]/', '', $value);
        
        // Handle Indonesian format: 46.780,00 -> 46780.00
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleaned)) {
            // Indonesian format with thousands separator (.) and decimal comma (,)
            $cleaned = str_replace('.', '', $cleaned); // Remove thousands separators
            $cleaned = str_replace(',', '.', $cleaned); // Replace decimal comma with dot
        }
        // Handle format: 46,780.00 -> 46780.00  
        elseif (preg_match('/^\d{1,3}(,\d{3})*\.\d{2}$/', $cleaned)) {
            // US format with thousands separator (,) and decimal dot (.)
            $cleaned = str_replace(',', '', $cleaned); // Remove thousands separators
        }
        // Handle simple format: just remove any remaining separators except last decimal
        else {
            // Find last decimal point or comma
            $lastDot = strrpos($cleaned, '.');
            $lastComma = strrpos($cleaned, ',');
            
            if ($lastDot !== false && $lastComma !== false) {
                // Both exist, use the later one as decimal separator
                if ($lastDot > $lastComma) {
                    // Dot is decimal separator
                    $cleaned = str_replace(',', '', $cleaned);
                } else {
                    // Comma is decimal separator
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            } elseif ($lastComma !== false) {
                // Only comma exists - treat as decimal separator if in last 3 positions
                if (strlen($cleaned) - $lastComma <= 3) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }
            }
        }
        
        // Convert to float
        $numericValue = (float)$cleaned;
        
        return $numericValue;
    }

    /**
     * Calculate DPP (Dasar Pengenaan Pajak) with no decimal places
     * 
     * @param float $totalAmount The total amount
     * @return int The DPP amount
     */
    public static function calculateDPP($totalAmount)
    {
        return self::roundToWholeNumber($totalAmount);
    }

    /**
     * Calculate DPP 11/12 with no decimal places
     * 
     * @param float $dpp The DPP amount
     * @return int The DPP 11/12 amount
     */
    public static function calculateDPP1112($dpp)
    {
        $dpp1112 = $dpp * (11/12);
        return self::roundToWholeNumber($dpp1112);
    }

    /**
     * Calculate PPN (12% x DPP 11/12) with no decimal places
     * 
     * @param float $dpp1112 The DPP 11/12 amount
     * @return int The PPN amount
     */
    public static function calculatePPN($dpp1112)
    {
        $ppn = $dpp1112 * 0.12;
        return self::roundToWholeNumber($ppn);
    }

    /**
     * Calculate grand total (DPP + PPN) with no decimal places
     * 
     * @param float $dpp The DPP amount
     * @param float $ppn The PPN amount
     * @return int The grand total
     */
    public static function calculateGrandTotal($dpp, $ppn)
    {
        $grandTotal = $dpp + $ppn;
        return self::roundToWholeNumber($grandTotal);
    }

    // === PENERIMAAN SPECIFIC METHODS ===

    /**
     * Format decimal value to 2 decimal places (for penerimaan)
     * 
     * @param float|string $value The value to format
     * @return float The formatted value with 2 decimal places
     */
    public static function formatDecimal($value)
    {
        return self::roundToTwoDecimals((float)$value);
    }

    /**
     * Multiply two decimal values with 2 decimal precision
     * 
     * @param float $a First value
     * @param float $b Second value
     * @return float The result with 2 decimal places
     */
    public static function multiplyDecimal($a, $b)
    {
        $result = (float)$a * (float)$b;
        return self::roundToTwoDecimals($result);
    }

    /**
     * Divide two decimal values with 2 decimal precision
     * 
     * @param float $a First value (dividend)
     * @param float $b Second value (divisor)
     * @return float The result with 2 decimal places
     */
    public static function divideDecimal($a, $b)
    {
        if ($b == 0) {
            return 0;
        }
        $result = (float)$a / (float)$b;
        return self::roundToTwoDecimals($result);
    }

    /**
     * Add two decimal values with 2 decimal precision
     * 
     * @param float $a First value
     * @param float $b Second value
     * @return float The result with 2 decimal places
     */
    public static function addDecimal($a, $b)
    {
        $result = (float)$a + (float)$b;
        return self::roundToTwoDecimals($result);
    }

    /**
     * Subtract two decimal values with 2 decimal precision
     * 
     * @param float $a First value (minuend)
     * @param float $b Second value (subtrahend)
     * @return float The result with 2 decimal places
     */
    public static function subtractDecimal($a, $b)
    {
        $result = (float)$a - (float)$b;
        return self::roundToTwoDecimals($result);
    }
} 