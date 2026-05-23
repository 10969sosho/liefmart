<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Format Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for invoice numbering format.
    | The format follows: {counter}/{yearMonth}/{suffix}/{taxCode}
    | Example: 0001/2601/AMP/01 for PKP, 0001/2601/AMP/02 for NON PKP
    |
    */

    'format' => [
        'suffix' => 'AMP',
        'suffix_offline' => 'AMP-KOS',
        'suffix_online' => 'AMP-OL',
        'counter_length' => 4,
        'year_month_format' => 'ym', // YYMM format
        'pkp_tax_code' => '01',
        'non_pkp_tax_code' => '02',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax ID Mapping for Offline Sales
    |--------------------------------------------------------------------------
    |
    | Mapping tax_id to category, sales_type, and tax_status for offline sales.
    | This is used by FinanceOffline model to determine the correct
    | invoice sequence parameters.
    |
    */
    'offline_mapping' => [
        // KOPI - ONLINE - PKP
        1 => [
            'category' => 'KOPI',
            'sales_type' => 'ONLINE',
            'tax_status' => 'PKP',
        ],
        // KOPI - ONLINE - NON_PKP
        2 => [
            'category' => 'KOPI',
            'sales_type' => 'ONLINE',
            'tax_status' => 'NON_PKP',
        ],
        // SKINCARE - ONLINE - PKP (HGN -> PKP)
        3 => [
            'category' => 'SKINCARE',
            'sales_type' => 'ONLINE',
            'tax_status' => 'PKP',
        ],
        // SKINCARE - ONLINE - NON_PKP (LM -> NON PKP)
        4 => [
            'category' => 'SKINCARE',
            'sales_type' => 'ONLINE',
            'tax_status' => 'NON_PKP',
        ],
        // KOPI - OFFLINE - PKP
        5 => [
            'category' => 'KOPI',
            'sales_type' => 'OFFLINE',
            'tax_status' => 'PKP',
        ],
        // KOPI - OFFLINE - NON_PKP
        6 => [
            'category' => 'KOPI',
            'sales_type' => 'OFFLINE',
            'tax_status' => 'NON_PKP',
        ],
        // SKINCARE - OFFLINE - PKP (HGN -> PKP)
        7 => [
            'category' => 'SKINCARE',
            'sales_type' => 'OFFLINE',
            'tax_status' => 'PKP',
        ],
        // SKINCARE - OFFLINE - NON_PKP (LM -> NON PKP)
        8 => [
            'category' => 'SKINCARE',
            'sales_type' => 'OFFLINE',
            'tax_status' => 'NON_PKP',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monthly Reset Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for monthly counter reset functionality.
    |
    */
    'monthly_reset' => [
        'enabled' => true,
        'auto_create_new_month' => true,
    ],
];