<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External integrations (placeholders until enabled)
    |--------------------------------------------------------------------------
    |
    | See docs/development/development_notes.txt and env_checklist.txt.
    |
    */

    'mail_enabled' => (bool) env('GK_MAIL_ENABLED', false),

    'mapbox_enabled' => (bool) env('GK_MAPBOX_ENABLED', false),

    'mapbox_token' => env('MAPBOX_TOKEN', env('MAPBOX_ACCESS_TOKEN')),

    'mapbox_style' => env('MAPBOX_STYLE', 'mapbox/streets-v12'),

    'paymongo_enabled' => (bool) env('GK_PAYMONGO_ENABLED', false),

];
