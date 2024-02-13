<?php
return [
    'vehicles'        => env('ERP_URL_VEHICLES',
        "http://172.16.0.35:7048/Pars_Pregolive/ODataV4/Company(%27Pars%27)/Vehicle_Card_API"),
    'items'           => env('ARIAN_URL_ITEMS', "http://172.16.0.35:7048/Pars/OData/Company('Pars')/ItemQuery"),
    'make_codes'      => env('ARIAN_URL_MAKE_CODES',
        "http://172.16.0.35:7048/Pars/OData/Company('Pars')/Make_List_API"),
    'basket'          => env('WSDL_BASKET', 'http://172.16.0.35:7047/Pars/WS/Pars/Codeunit/Item_Reclass_Journal_API'),
    'post_production' => env('WSDL_POST_PRODUCTION',
        'http://172.16.0.35:7047/Pars_Pregolive/WS/Pars/Codeunit/GeneralAPI'),
    'block_unblock' => env('WSDL_BLOCK_UNBLOCK',
        'http://172.16.0.35:7047/Pars_Pregolive/WS/Pars/Codeunit/GeneralAPI'),
    'auth'            => [
        env('LDAP_USERNAME', ''),
        env('LDAP_PASSWORD', ''),
        env('LDAP_AUTH_TYPE', 'ntlm')
    ]
];
