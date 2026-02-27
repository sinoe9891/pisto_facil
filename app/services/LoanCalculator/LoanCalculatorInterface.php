<?php
// app/services/LoanCalculator/LoanCalculatorInterface.php

namespace App\Services\LoanCalculator;

interface LoanCalculatorInterface
{
    /**
     * Build the full amortization schedule or payment plan
     * Returns array of installments and summary data
     */
    public function buildSchedule(array $loanData): array;

    /**
     * Calculate current outstanding interest + late fees as of today
     */
    public function calculateCurrentInterest(array $loan, \DateTime $asOf): array;

    /**
     * Apply a payment and return updated loan state
     */
    public function applyPayment(array $loan, float $amount, \DateTime $paymentDate): array;
}