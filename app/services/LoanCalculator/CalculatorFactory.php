<?php
// app/services/LoanCalculator/CalculatorFactory.php

namespace App\Services\LoanCalculator;

class CalculatorFactory
{
    public static function make(string $loanType): LoanCalculatorInterface
    {
        return match (strtoupper($loanType)) {
            'A'     => new LevelPaymentCalculator(),
            'B'     => new VariablePaymentCalculator(),
            'C'     => new MonthlySimpleInterestCalculator(),
            default => throw new \InvalidArgumentException("Tipo de préstamo desconocido: {$loanType}"),
        };
    }
}