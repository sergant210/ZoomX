<?php

return [
    'ZoomX' => [
        'file' => 'zoomx',
        'description' => '',
        'events' => [
//            'OnInitCulture' => ['priority' => -1000],
            'OnMODXInit' => ['priority' => -1000],
            'OnSiteRefresh' => [],
            'OnCacheUpdate' => [],
        ],
    ],
];