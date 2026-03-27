<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashflow period start (month / day)
    |--------------------------------------------------------------------------
    |
    | Each new period begins on this date. Opening balance does not carry from
    | the day before the period start (e.g. no Feb 28 → Mar 1 carry). Within
    | the period, closing balance still carries to the next day that exists.
    |
    */

    'period_start_month' => 3,
    'period_start_day' => 1,

];
