<?php

return [
    /**
     * Demo + normalization catalog for RICA disease intelligence.
     * Used by seeders and {@see App\Support\RicaDiseaseLabelResolver}.
     */
    'catalog' => [
        [
            'name' => 'Foot and Mouth Disease',
            'condition' => 'Salivation, vesicles on mouth and feet; suspected FMD',
            'condemnation_reason' => 'Suspected foot and mouth lesions; carcass condemned',
            'keywords' => ['foot and mouth', 'fmd', 'vesicle', 'salivation', 'lameness'],
        ],
        [
            'name' => 'Histomoniasis',
            'condition' => 'Enlarged liver with necrotic foci; suspected histomoniasis',
            'condemnation_reason' => 'Histomoniasis lesions in liver; unfit for human consumption',
            'keywords' => ['histomon', 'blackhead', 'liver necrosis'],
        ],
        [
            'name' => 'Lumpy Skin Disease',
            'condition' => 'Cutaneous nodules and fever; suspected lumpy skin disease',
            'condemnation_reason' => 'Lumpy skin disease nodules; partial condemnation',
            'keywords' => ['lumpy skin', 'nodules', 'cutaneous'],
        ],
        [
            'name' => 'Mastitis',
            'condition' => 'Swollen udder and abnormal milk; suspected mastitis',
            'condemnation_reason' => 'Mastitis — mammary tissue condemned',
            'keywords' => ['mastitis', 'udder', 'mammary'],
        ],
        [
            'name' => 'Brucellosis',
            'condition' => 'Retained placenta and joint swelling; suspected brucellosis',
            'condemnation_reason' => 'Brucellosis suspicion; organ condemnation',
            'keywords' => ['brucell', 'abortion', 'retained placenta'],
        ],
        [
            'name' => 'PPR',
            'condition' => 'Nasal discharge and pneumonia signs; suspected PPR',
            'condemnation_reason' => 'PPR lesions in lungs; condemned',
            'keywords' => ['ppr', 'peste des petits', 'pneumonia'],
        ],
        [
            'name' => 'Salmonellosis',
            'condition' => 'Diarrhoea and dehydration; suspected salmonellosis',
            'condemnation_reason' => 'Salmonellosis — septic lesions condemned',
            'keywords' => ['salmonell', 'diarrhoea', 'dehydration'],
        ],
        [
            'name' => 'Contagious Bovine Pleuropneumonia',
            'condition' => 'Respiratory distress and coughing; suspected CBPP',
            'condemnation_reason' => 'CBPP lung adhesions; condemned',
            'keywords' => ['cbpp', 'pleuropneumonia', 'respiratory'],
        ],
    ],

    'checklist_item_labels' => [
        'locomotion' => 'Locomotor disorder',
        'respiratory_system' => 'Respiratory disease',
        'circulatory_system' => 'Circulatory disease',
        'lymphnodes' => 'Lymphadenopathy',
        'hair_and_skin' => 'Dermatological condition',
        'mucus' => 'Mucosal disease',
        'temperature' => 'Febrile illness',
        'liver' => 'Hepatic disease',
        'lungs' => 'Pulmonary disease',
    ],
];
