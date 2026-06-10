<?php

declare(strict_types=1);

// config for Hwkdo/IntranetAppBueExports
return [
    'roles' => [
        'admin' => [
            'name' => 'App-BueExports-Admin',
            'permissions' => [
                'see-app-bue-exports',
                'manage-app-bue-exports',
            ],
        ],
        'user' => [
            'name' => 'App-BueExports-Benutzer',
            'permissions' => [
                'see-app-bue-exports',
            ],
        ],
    ],

    'bue_connection' => require __DIR__.'/bue-exports-connection.php',

    'stamm_views' => [
        'gewerke' => 'hwkuserro.STAMM_GEWERKE',
        'orte' => 'hwkuserro.STAMM_ORTE',
        'landkreise' => 'hwkuserro.STAMM_LANDKREISE',
    ],

    'labeled_stamm_views' => [
        'anlagen' => [
            'view' => 'hwkuserro.STAMM_ANLAGEN',
            'value_column' => 'ANLAGE',
            'label_column' => 'ANLAGEBEZEICHNUNG',
        ],
    ],

    // Oracle/yajra-oci8 liefert Attribute in Kleinbuchstaben (name, nicht NAME)
    'stamm_value_column' => 'name',

    'stamm_cache_ttl' => 3600,

    'default_max_records' => 10_000,
];
