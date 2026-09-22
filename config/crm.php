<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Radius Validasi Kunjungan (Geo-tagging)
    |--------------------------------------------------------------------------
    | Jarak maksimum (dalam meter) antara koordinat Sales dan lokasi target
    | (Sekolah / Perusahaan) agar kunjungan otomatis berstatus Valid.
    */
    'visit_radius_meters' => (float) env('CRM_VISIT_RADIUS_METERS', 100),

    /*
    |--------------------------------------------------------------------------
    | Tier Sekolah & Budget Maksimum Kunjungan
    |--------------------------------------------------------------------------
    | Plafon anggaran maksimum berdasarkan Tier Sekolah.
    */
    'school_tier_budgets' => [
        'A' => (float) env('CRM_TIER_A_BUDGET', 10000000),
        'B' => (float) env('CRM_TIER_B_BUDGET', 5000000),
        'C' => (float) env('CRM_TIER_C_BUDGET', 2500000),
    ],
];
