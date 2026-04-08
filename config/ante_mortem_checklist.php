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
            'behavior' => ['label' => 'Behavior', 'type' => 'normal_abnormal'],
            'gait_posture' => ['label' => 'Gait / posture', 'type' => 'normal_abnormal'],
            'body_condition' => ['label' => 'Body condition', 'type' => 'normal_abnormal'],
            'respiratory_signs' => ['label' => 'Respiratory signs', 'type' => 'yes_no'],
            'diarrhea' => ['label' => 'Diarrhea', 'type' => 'yes_no'],
            'wounds' => ['label' => 'Wounds', 'type' => 'yes_no'],
            'fever' => ['label' => 'Fever', 'type' => 'yes_no'],
            'hygiene' => ['label' => 'Hygiene', 'type' => 'normal_abnormal'],
        ],

        'sheep_goats' => [
            'behavior' => ['label' => 'Behavior', 'type' => 'normal_abnormal'],
            'body_condition' => ['label' => 'Body condition', 'type' => 'normal_abnormal'],
            'lesions' => ['label' => 'Lesions', 'type' => 'yes_no'],
            'nasal_discharge' => ['label' => 'Nasal discharge', 'type' => 'yes_no'],
            'ocular_discharge' => ['label' => 'Ocular discharge', 'type' => 'yes_no'],
            'lameness' => ['label' => 'Lameness', 'type' => 'yes_no'],
            'fleece_cleanliness' => ['label' => 'Fleece cleanliness', 'type' => 'normal_abnormal'],
        ],

        'pigs' => [
            'body_condition' => ['label' => 'Body condition', 'type' => 'normal_abnormal'],
            'cough' => ['label' => 'Cough', 'type' => 'yes_no'],
            'skin_lesions' => ['label' => 'Skin lesions', 'type' => 'yes_no'],
            'diarrhea' => ['label' => 'Diarrhea', 'type' => 'yes_no'],
            'lethargy' => ['label' => 'Lethargy', 'type' => 'yes_no'],
            'cleanliness' => ['label' => 'Cleanliness', 'type' => 'normal_abnormal'],
        ],

        'poultry' => [
            'activity_level' => ['label' => 'Activity level', 'type' => 'normal_abnormal'],
            'feather_condition' => ['label' => 'Feather condition', 'type' => 'normal_abnormal'],
            'droppings' => ['label' => 'Droppings', 'type' => 'yes_no'],
            'respiratory_signs' => ['label' => 'Respiratory signs', 'type' => 'yes_no'],
            'deformities' => ['label' => 'Deformities', 'type' => 'yes_no'],
        ],
    ],

    'value_options' => [
        'normal_abnormal' => ['normal', 'abnormal'],
        'yes_no' => ['yes', 'no'],
    ],
];
