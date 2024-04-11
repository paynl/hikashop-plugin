<?php

class Pay_Helper {

    /**
     * Bepaal de status aan de hand van het statusid.
     * Over het algemeen worden allen de statussen -90(CANCEL), 20(PENDING) en 100(PAID) gebruikt
     * 
     * @param int $statusId
     * @return string De status
     */
      public static function getStateText($stateId) {
        switch ($stateId) {
            case 80:
            case -51:
                return 'CHECKAMOUNT';
            case 100:
                return 'PAID';
            default:
                if ($stateId < 0) {
                    return 'CANCEL';
                } else {
                    return 'PENDING';
                }
        }
    }

    //remove all empty nodes in an array
    public static function filterArrayRecursive($array) {
        $newArray = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::filterArrayRecursive($value);
            }
            if (!empty($value)) {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    public static function sortPaymentOptions($paymentOptions){
        uasort($paymentOptions, 'sortPaymentOptions');
        return $paymentOptions;
    }
    /**
     * Get the nearest number
     *
     * @param int $number
     * @param array $numbers
     * @return int|bool nearest number false on error
     */
    private static function nearest($number, $numbers)
    {
        $output = FALSE;
        $number = intval($number);
        if (is_array($numbers) && count($numbers) >= 1) {
            $NDat = array();
            foreach ($numbers as $n) {
                $NDat[abs($number - $n)] = $n;
            }
            ksort($NDat);
            $NDat = array_values($NDat);
            $output = $NDat[0];
        }
        return $output;
    }
    /**
     * Determine the tax class to send to Pay.nl
     *
     * @param int|float $amountInclTax
     * @param int|float $taxAmount
     * @return string The tax class (N, L or H)
     */
    public static function calculateTaxClass($amountInclTax, $taxAmount)
    {
        $taxClasses = array(
            0 => 'N',
            6 => 'L',
            21 => 'H'
        );
        // return 0 if amount or tax is 0
        if ($taxAmount == 0 || $amountInclTax == 0) {
            return $taxClasses[0];
        }
        $amountExclTax = $amountInclTax - $taxAmount;
        $taxRate = ($taxAmount / $amountExclTax) * 100;
        $nearestTaxRate = self::nearest($taxRate, array_keys($taxClasses));
        return ($taxClasses[$nearestTaxRate]);
    }
}
function sortPaymentOptions($a,$b){
    return strcmp($a['name'], $b['name']);
}