<?php

return [
    'navigation_label' => 'Job Offers',
    'model_label' => 'Job Offer',
    'plural_model_label' => 'Job Offers',

    'fields' => [
        'title' => 'Job title',
        'location' => 'Location',
        'contract_type' => 'Contract type',
        'salary' => 'Salary',
        'deadline' => 'Deadline',
        'is_active' => 'Active',
        'description' => 'Detailed description',
        'requirements' => 'Requirements',
        'benefits' => 'Benefits',
    ],

    'help' => [
        'is_active' => 'When enabled, the offer is published and the publish date is set.',
    ],

    'columns' => [
        'title' => 'Title',
        'location' => 'Location',
        'contract_type' => 'Contract type',
        'is_active' => 'Active',
        'deadline' => 'Deadline',
    ],

    'filters' => [
        'active' => 'Active',
    ],

    'actions' => [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
    ],
];

