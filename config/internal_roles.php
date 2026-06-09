<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internal Roles Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration maps internal staff roles to their respective
    | Filament panels, email domains, and access paths.
    |
    */

    'unit_survey' => [
        'domain' => 'unitsurvey.com',
        'panel' => 'unit-survey',
        'path' => '/internal/unit-survey',
        'label' => 'Unit Survey',
    ],

    'unit_perencanaan' => [
        'domain' => 'unitperencanaan.com',
        'panel' => 'unit-perencanaan',
        'path' => '/internal/unit-perencanaan',
        'label' => 'Unit Perencanaan',
    ],

    'unit_konstruksi' => [
        'domain' => 'unitkonstruksi.com',
        'panel' => 'unit-konstruksi',
        'path' => '/internal/unit-konstruksi',
        'label' => 'Unit Konstruksi',
    ],

    'unit_te' => [
        'domain' => 'unitte.com',
        'panel' => 'unit-te',
        'path' => '/internal/unit-te',
        'label' => 'Unit TE',
    ],

    'supervisor' => [
        'domain' => 'supervisor.com',
        'panel' => 'supervisor',
        'path' => '/internal/supervisor',
        'label' => 'Supervisor',
    ],

    'admin_layanan' => [
        'domain' => 'adminlayanan.com',
        'panel' => 'admin-layanan',
        'path' => '/internal/admin-layanan',
        'label' => 'Admin Layanan',
    ],

    'admin_pelayanan' => [
        'domain' => 'adminpelayanan.com',
        'panel' => 'admin-layanan',
        'path' => '/internal/admin-layanan',
        'label' => 'Admin Layanan',
    ],
];