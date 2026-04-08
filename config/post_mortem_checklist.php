<?php

return [
    'species_aliases' => [
        'cattle' => 'cattle',
        'cow' => 'cattle',

        'sheep' => 'sheep_goats',
        'goat' => 'sheep_goats',
        'goats' => 'sheep_goats',
        'sheep & goats' => 'sheep_goats',
        'sheep and goats' => 'sheep_goats',

        'pig' => 'pigs',
        'pigs' => 'pigs',

        'poultry' => 'poultry',
    ],

    'checklists' => [
        'cattle' => [
            'carcass_lesions' => ['label' => 'Carcass lesions', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'abscesses' => ['label' => 'Abscesses', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'liver_flukes' => ['label' => 'Liver flukes', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'lung_abnormalities' => ['label' => 'Lung abnormalities', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'heart_abnormalities' => ['label' => 'Heart abnormalities', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'lymph_node_abnormalities' => ['label' => 'Lymph node abnormalities', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],

            'organ_liver' => ['label' => 'Liver', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lungs' => ['label' => 'Lungs', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_heart' => ['label' => 'Heart', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_spleen' => ['label' => 'Spleen', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => false],
            'organ_kidneys' => ['label' => 'Kidneys', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lymph_nodes' => ['label' => 'Lymph nodes', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
        ],

        'sheep_goats' => [
            'carcass_lesions' => ['label' => 'Carcass lesions', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'internal_parasites' => ['label' => 'Internal parasites', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'liver_abscesses' => ['label' => 'Liver abscesses', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'lung_lesions' => ['label' => 'Lung lesions', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],

            'organ_liver' => ['label' => 'Liver', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lungs' => ['label' => 'Lungs', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_heart' => ['label' => 'Heart', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_spleen' => ['label' => 'Spleen', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => false],
            'organ_kidneys' => ['label' => 'Kidneys', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lymph_nodes' => ['label' => 'Lymph nodes', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
        ],

        'pigs' => [
            'skin_lesions' => ['label' => 'Skin lesions', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'cysts_taenia' => ['label' => 'Cysts (e.g. Taenia)', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'lung_damage' => ['label' => 'Lung damage', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'liver_damage' => ['label' => 'Liver damage', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'lymph_node_abnormalities' => ['label' => 'Lymph node abnormalities', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],

            'organ_liver' => ['label' => 'Liver', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lungs' => ['label' => 'Lungs', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_heart' => ['label' => 'Heart', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_kidneys' => ['label' => 'Kidneys', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lymph_nodes' => ['label' => 'Lymph nodes', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
        ],

        'poultry' => [
            'carcass_color_abnormalities' => ['label' => 'Carcass color abnormalities', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],
            'organ_abnormalities' => ['label' => 'Organ abnormalities', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'air_sac_condition' => ['label' => 'Air sac condition', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'liver_spots' => ['label' => 'Liver spots', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'pericarditis' => ['label' => 'Pericarditis', 'type' => 'yes_no', 'category' => 'organ', 'critical' => true],
            'parasites' => ['label' => 'Parasites', 'type' => 'yes_no', 'category' => 'carcass', 'critical' => true],

            'organ_liver' => ['label' => 'Liver', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_heart' => ['label' => 'Heart', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_lungs' => ['label' => 'Lungs', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'organ_intestines' => ['label' => 'Intestines', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
        ],
    ],

    'value_options' => [
        'normal_abnormal' => ['normal', 'abnormal'],
        'yes_no' => ['yes', 'no'],
    ],
];
