<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificate Cryptographic Signing Key
    |--------------------------------------------------------------------------
    |
    | Separate signing keys are used to cryptographically sign generated
    | certificates using HMAC-SHA256, protecting against database tampering
    | or URL manipulation. Supporting multiple key versions facilitates
    | rotation-safe signing lifecycles.
    |
    */

    'active_version' => env('CERT_SIGNING_KEY_VERSION', 'v1'),

    'keys' => [
        'v1' => env('CERT_SIGNING_KEY_V1', 'default-cert-key-v1-secret-here'),
        'v2' => env('CERT_SIGNING_KEY_V2'),
    ],
];
