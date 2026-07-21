<?php
return [

    'bom' =>env('BOM_LIST_URL',"http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/Bomline"),
    'pdi_block_reason_id' =>env('PDI_BLOCK_REASON_ID'),
    'vehicles'  => env('ERP_URL_VEHICLES',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/Vehicle_List_API"),
    'vehicle_change'  => env('GENERAL_API',
        "http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI"),
    'production_header'  => env('ERP_URL_PRODUCTION_HEADER',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/ProductionHeader"),
    'cross_reference'  => env('ERP_URL_CROSS_REFERENCE',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/Item_Cross_Reference_API"),
    'block_pdi'  => env('ERP_URL_BLOCK_PDI',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/PDI_Block_API"),
    'items'     => env('ARIAN_URL_ITEMS',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/ItemQuery"),
    'shifts'     => env('ARIAN_URL_SHIFTS',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/WorkShifts"),
    'service_items'     => env('ARIAN_URL_SERVICE_ITEMS',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/Service_Item_List"),

    'batch_name' => env('BATCH_NAME',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/TransferBatchListAPI"),
    'component' => env('ARIAN_URL_COMPONENT',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/ComponentTracking"),

    'make_codes'       => env('ARIAN_URL_MAKE_CODES',
        "http://172.16.0.154:7048/PreBC/ODataV4/Company('Pars')/Make_List_API"),
    'basket'           => env('GENERAL_API', 'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'check_basket'           => env('GENERAL_API', 'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'basket_batch'           => env('GENERAL_API', 'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),

    'display_board'    => 'http://172.16.0.154:7248/APIBC/api/arianCapital/production/v1.0/productionBoardEntries',
//    'display_board'    => env('GENERAL_API',
//        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'pdi_result'    => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),

        'synchronous_v2'    => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),



    'barcode'    => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),

    'post_production'  => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'block_unblock'    => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),

    'shortage'    => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'transfer_vehicle' => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'update_qc_status' => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'gate'             => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'exit_request'     => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'approve'          => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'return_request'   => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'old_gate'         => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'print'         => env('GENERAL_API',
        'http://172.16.0.154:7047/PreBC/WS/Pars/Codeunit/GeneralAPI'),
    'auth'             => [
        env('LDAP_USERNAME', ''),
        env('LDAP_PASSWORD', ''),
        env('LDAP_AUTH_TYPE', 'ntlm')
    ],
    'workgroups'       => env('LDAP_WORKGROUP', 'arian-motors.com'),
    'share_server'     => env('SHARE_SERVER', '172.16.0.44'),
    'share_folder'     => env('SHARE_FOLDER', 'ERP-Link/Sales Documents')
];
