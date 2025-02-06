<?php
// Config file for configuring different platforms for geteduroam
return [
    'apps' => [
        'android' => [
            'url' => 'https://play.google.com/store/apps/details?id=app.eduroam.geteduroam',
            'name' => 'Android',
            'enabled' => true,
        ],
        'ios' => [
            'url' => 'https://apps.apple.com/app/geteduroam/id1504076137',
            'name' => 'iOS',
            'enabled' => true,
        ],
        'windows' => [
            'url' => 'https://dl.eduroam.app/windows/x86_64/geteduroam.exe',
            'name' => 'Windows',
            'enabled' => true,
        ],
        'huawei' => [
            'url' => 'https://appgallery.huawei.com/app/C104231893',
            'name' => 'Huawei',
            'enabled' => true,
        ],
    ],

    'os_config' => [
        'mobileconfig' => [
            'url' => "/profiles/mac/",
            'name' => 'macOS',
            'enabled' => true,
        ],
        'onc' => [
            'url' => "/profiles/onc/",
            'name' => 'ChromeOS',
            'enabled' => true,
        ],
    ],
    'manual' => [
        'url' => "/profiles/new/",
        'name' => 'Manual',
        'enabled' => true,
    ],
];

?>