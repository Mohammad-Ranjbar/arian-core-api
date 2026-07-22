<?php
return [
    'JAC'      => [
        [
            'type'   => 'custom_engine',
            'prefix' => ''
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'LAMARI'   => [
        [
            'type'   => 'custom_engine',

            'prefix' => [
             'ESPSE010DP' => 'DFMC15TP1*',//echo without prefix - check * in middle and end
             'DSPCO010DP' => '4A95TD',//ima
             'KSPCO020DP' => '',//hirkani
             'KSPCO010CP' => '',//neo
             'DSPCO020DP' => '',//lamariX
            ]
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'LAMACO'   => [
        [
            'type'   => 'custom_engine',
            'prefix' => 'SFG15TA',
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'UNI'   => [
        [
            'type'   => 'custom_engine',
            'prefix' => '',
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'SHACMOTO' => [
        [
            'type'   => 'custom_engine',
            'prefix' => ''
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'LMR-TRUCKS' => [
        [
            'type'   => 'custom_engine',
            'prefix' => ''
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'MITSUBISHI' => [
        [
            'type'   => 'custom_engine',
            'prefix' => ''
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'XGMA'     => [
        [
            'type'   => 'custom_engine',
            'prefix' => ''
        ],
        [
            'type' => 'body',
        ],
        [
            'type' => 'gearbox',
        ]
    ],
    'erp_map'  => [
        'engine'        => 2,
        'custom_engine' => 2,
        'body'          => 1,
        'gearbox'       => 3,
    ]
];
