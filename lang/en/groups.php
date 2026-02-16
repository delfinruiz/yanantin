<?php

return [
    'navigation_label' => 'Chat Groups',
    'model_label' => 'Group',
    'plural_model_label' => 'Groups',
    'fields' => [
        'name' => 'Name',
        'type' => 'Type',
        'public' => 'Public',
        'private' => 'Private',
        'description' => 'Description',
        'avatar' => 'Avatar',
        'members' => 'Members',
    ],
    'columns' => [
        'avatar' => 'Avatar',
        'name' => 'Name',
        'type' => 'Type',
        'description' => 'Description',
        'members' => 'Members',
        'blocked' => 'Blocked',
        'created_at' => 'Created At',
    ],
    'filters' => [
        'type' => 'Type',
    ],
    'actions' => [
        'block_users' => 'Block users',
        'unblock_users' => 'Unblock users',
        'view_blocked' => 'View blocked',
    ],
    'notifications' => [
        'blocked_ok' => 'Users blocked',
        'unblocked_ok' => 'Users unblocked',
    ],
];
