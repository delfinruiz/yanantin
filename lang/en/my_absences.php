<?php

return [
    'navigation_label' => 'My Absences',
    'navigation_group' => 'My Applications',
    'model_label' => 'My Absence',
    'plural_model_label' => 'My Absences',
    'columns' => [
        'type' => 'Type',
        'start_date' => 'From',
        'end_date' => 'To',
        'days' => 'Days',
        'status' => 'Status',
    ],
    'status' => [
        'pending' => 'Pending',
        'approved_supervisor' => 'Approved (Sup.)',
        'approved_hr' => 'Approved (Final)',
        'rejected' => 'Rejected',
    ],
    'notifications' => [
        'cannot_create' => [
            'title' => 'Cannot create request',
        ],
        'no_department' => [
            'body' => 'You do not belong to any department, so you cannot submit requests.',
        ],
        'no_supervisor' => [
            'body' => 'The department you belong to does not have an assigned supervisor to authorize the request.',
        ],
        'cannot_edit' => [
            'title' => 'Cannot edit',
        ],
        'only_pending' => [
            'body' => 'Only pending requests can be edited.',
        ],
    ],
];
