<?php

return [
    'species_aliases' => [
        'cattle' => 'all_species',
        'cow' => 'all_species',
        'sheep' => 'all_species',
        'goat' => 'all_species',
        'goats' => 'all_species',
        'sheep & goats' => 'all_species',
        'sheep and goats' => 'all_species',
        'pig' => 'all_species',
        'pigs' => 'all_species',
        'poultry' => 'all_species',
        'other' => 'all_species',
    ],

    'checklists' => [
        'all_species' => [
            'locomotion' => ['label' => 'Locomotion', 'type' => 'normal_abnormal'],
            'body_condition' => ['label' => 'Body condition (foot note)', 'type' => 'normal_abnormal'],
            'temperature' => ['label' => 'Temperature', 'type' => 'normal_abnormal'],
            'lymphnodes' => ['label' => 'Lymphnodes', 'type' => 'normal_abnormal'],
            'mucus' => ['label' => 'Mucus', 'type' => 'normal_abnormal'],
            'hair_and_skin' => ['label' => 'Hair and skin', 'type' => 'normal_abnormal'],
            'respiratory_system' => ['label' => 'Respiratory system', 'type' => 'normal_abnormal'],
            'circulatory_system' => ['label' => 'Circulatory system', 'type' => 'normal_abnormal'],
            'decision' => ['label' => 'Decision', 'type' => 'decision'],
            'observation' => ['label' => 'Observation', 'type' => 'free_text'],
        ],
    ],

    'value_options' => [
        'normal_abnormal' => ['normal', 'abnormal'],
        'yes_no' => ['yes', 'no'],
        'decision' => ['approved', 'rejected'],
        'free_text' => [],
    ],
];
