<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route groups
    |--------------------------------------------------------------------------
    |
    | Faza 10 §3: guests must never receive dashboard.* route names/URIs in
    | the page source. app.blade.php passes the "guest" group to @routes for
    | unauthenticated requests; authenticated requests get every route, since
    | policies (not route-name secrecy) are what actually guard dashboard
    | access. Patterns not ending in "*" that this list wildcards (properties.*,
    | favorites.*, offer-property.*, create-request.*, locale.*) only ever
    | contain public route names — see the $publicPages closure in web.php.
    |
    */
    'groups' => [
        'guest' => [
            'home',
            'about',
            'properties.*',
            'offer-property.*',
            'create-request.*',
            'favorites.*',
            'locale.*',
            'login',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
        ],
    ],

];
