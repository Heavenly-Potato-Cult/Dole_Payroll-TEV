<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payroll Computation Mode
    |--------------------------------------------------------------------------
    |
    | Determines whether payroll is computed on a semi-monthly or monthly basis.
    |
    | - false (default): Semi-monthly computation (1st and 2nd cutoffs)
    |   - Salary/PERA/RATA are divided by 2 for each cutoff
    |   - Denominator is 22 working days per cutoff
    |
    | - true: Monthly computation
    |   - Full monthly salary/PERA/RATA are used
    |   - Denominator is 44 working days per month (22 × 2)
    |   - Batches are kept for viewing purposes only
    |
    */
    'monthly_computation' => env('PAYROLL_MONTHLY_COMPUTATION', false),
];
