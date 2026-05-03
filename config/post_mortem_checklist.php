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
            'liver' => ['label' => 'Liver', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'spleen' => ['label' => 'Spleen', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'kidneys' => ['label' => 'Kidneys', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'heart' => ['label' => 'Heart', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'lungs' => ['label' => 'Lungs', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'intestines' => ['label' => 'Intestines', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'stomach' => ['label' => 'Stomach', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'lymphnodes' => ['label' => 'Lymphnodes', 'type' => 'normal_abnormal', 'category' => 'organ', 'critical' => true],
            'decision' => ['label' => 'Decision', 'type' => 'decision', 'category' => 'decision', 'critical' => true],
            'comment' => ['label' => 'Comment', 'type' => 'free_text', 'category' => 'decision', 'critical' => false],
        ],
    ],

    'value_options' => [
        'normal_abnormal' => ['normal', 'abnormal'],
        'yes_no' => ['yes', 'no'],
        'decision' => ['approved', 'rejected'],
        'free_text' => [],
    ],
];
