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

    'mapbox_min_zoom' => 8,

    'mapbox_max_zoom' => 17,

    /*
    | Fallback map camera when Settings map_center_* are missing.
    | Defaults: Metro Manila (see SettingSeeder).
    */
    'mapbox_center_lat' => (float) env('MAPBOX_CENTER_LAT', 14.5995),

    'mapbox_center_lng' => (float) env('MAPBOX_CENTER_LNG', 120.9842),

    'mapbox_initial_zoom' => (float) env('MAPBOX_INITIAL_ZOOM', 11),

    'paymongo_enabled' => (bool) env('GK_PAYMONGO_ENABLED', false),

    /*
    | When true, ActivityLogger resolves a human-readable location from the request IP
    | (best-effort; disabled by default).
    */
    'activity_ip_lookup' => (bool) env('GK_ACTIVITY_IP_LOOKUP', false),

];
