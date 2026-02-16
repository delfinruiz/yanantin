<?php

return [
    'model_label' => 'Event',
    'plural_model_label' => 'Events',
    'navigation_label' => 'Events',
    'navigation_group' => 'Communications',
    'field' => [
        'title' => 'Title',
        'calendar' => 'Calendar',
        'is_public' => 'Public',
        'is_personal' => 'Personal',
        'start' => 'Start',
        'end' => 'End',
        'all_day' => 'All Day',
        'color' => 'Color',
        'shared_with' => 'Shared With',
        'description' => 'Description',
        'attachments' => 'Attachments',
    ],
    'notification' => [
        'public_new_title' => 'New Public Event',
        'public_new_body' => ':manager has created the event ":title" in the ":calendar" calendar.',
        'shared_with_you_title' => 'Event Shared With You',
        'shared_with_you_body' => 'The event ":title" from calendar ":calendar" has been shared with you.',
    ],
];
