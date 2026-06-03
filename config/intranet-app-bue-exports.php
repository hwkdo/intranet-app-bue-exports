<?php

// config for Hwkdo/IntranetAppBueExports
return [
'roles' => [
        'admin' => [
            'name' => 'App-BueExports-Admin',
            'permissions' => [
                'see-app-bue-exports',
                'manage-app-bue-exports',
            ]
        ],
        'user' => [
            'name' => 'App-BueExports-Benutzer',
            'permissions' => [
                'see-app-bue-exports',                
            ]
        ],
]
];
