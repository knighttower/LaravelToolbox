<?php

namespace Knighttower\Toolbox\Helpers;

use NumberFormatter;
use Illuminate\Support\Number;

// convert/parse to standard dollar formats

class DollarAmountHelper
{
    /**
     * Helper Constructor
     */
    public function __construct()
    {
        //set to US currency
        setlocale(LC_MONETARY, 'en_US');
    }


    /**
     * Test if the value is in dollar format (currency) or valid decimal amount
     * Uses PHP's NumberFormatter for reliable parsing
     *
     * @param mixed $value
     * @param bool $decimalMode If true, also accepts plain decimal numbers as valid dollar amounts
     * @return bool
     */
    public function isDollarAmount($value, bool $decimalMode = false): bool
    {
        // Try currency format first
        $currencyFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $currencyParsed = $currencyFormatter->parse($value);

        if ($currencyParsed !== false) {
            return true;
        }

        // If decimal mode is enabled, also check for valid decimal numbers
        if ($decimalMode) {
            // Check if it's a valid number (including decimals)
            if (is_numeric($value)) {
                $numericValue = (float) $value;
                // Ensure it's a reasonable dollar amount (not negative, reasonable precision)
                return $numericValue >= 0 && $numericValue < PHP_FLOAT_MAX;
            }
        }

        return false;
    }


    /**
     * Remove currency symbols and formatting, returning clean numeric string
     * Uses NumberFormatter for proper currency parsing
     *
     * @param string $amount
     * @return string
     */
    private function removeSymbol(string $amount): string
    {
        // If it's already a plain number, return as-is
        if (is_numeric($amount)) {
            return $amount;
        }

        // Try to parse as currency first
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $parsed = $formatter->parse($amount);

        if ($parsed !== false) {
            return (string) $parsed;
        }

        // Fallback to regex for edge cases
        return preg_replace('/[\$,\s]/', '', $amount);
    }


    /**
     * Convert to properly formatted currency
     * Uses NumberFormatter for proper currency formatting
     *
     * @param string $amount
     * @param string $currencyCode Currency code (defaults to USD for dollar amounts)
     * @return string
     */
    public function toCurrency(string $amount, string $currencyCode = 'USD'): string
    {
        $amount = $this->removeSymbol($amount);

        if (!is_numeric($amount)) {
            return $amount;
        }

        // Use NumberFormatter for proper currency formatting
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $amount, $currencyCode);
    }


    /**
     * Convert currency to decimal float value
     * Uses improved removeSymbol method for proper parsing
     *
     * @param string $amount
     * @return float
     * @throws \InvalidArgumentException When the input cannot be converted to a valid number
     */
    private function toDecimal(string $amount): float
    {
        $cleanAmount = $this->removeSymbol($amount);

        if (!is_numeric($cleanAmount)) {
            throw new \InvalidArgumentException("Cannot convert '{$amount}' to a valid decimal amount");
        }

        // Convert to float and round to 2 decimal places for currency precision
        return round((float) $cleanAmount, 2);
    }


    /**
     * Convert into condensed string format (e.g., 1.2K, 3.4M, 5.6B)
     * Uses Laravel's Number class for proper abbreviation formatting
     *
     * @param int|string $amount
     * @return string Returns original value as string if conversion fails
     */
    public function toString($amount): string
    {
        try {
            $decimalAmount = $this->toDecimal($amount);
            // Use Laravel's Number::abbreviate for better formatting
            return Number::abbreviate($decimalAmount, precision: 1);
        } catch (\InvalidArgumentException $e) {
            // Return the original value as string if conversion fails
            return (string) $amount;
        }
    }
}