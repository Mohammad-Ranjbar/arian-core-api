<?php
return [
    'vehicles' => env('ERP_URL_VEHICLES',
        "http://172.16.0.35:7048/Pars/OData/Company(%27Pars%27)/Vehicle_Card_API"),
    'items'      => env('ARIAN_URL_ITEMS', "http://172.16.0.35:7048/Pars/OData/Company('Pars')/ItemQuery"),
    'make_codes' => env('ARIAN_URL_MAKE_CODES', "http://172.16.0.35:7048/Pars/OData/Company('Pars')/Make_List_API"),

    'auth' => [
        env('LDAP_USERNAME',''),
        env('LDAP_PASSWORD',''),
        env('LDAP_AUTH_TYPE','ntlm')
    ]
];
