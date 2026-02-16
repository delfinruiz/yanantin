<?php

return [
    'navigation_label' => 'Grupos de Chat',
    'model_label' => 'Grupo',
    'plural_model_label' => 'Grupos',
    'fields' => [
        'name' => 'Nombre',
        'type' => 'Tipo',
        'public' => 'Público',
        'private' => 'Privado',
        'description' => 'Descripción',
        'avatar' => 'Avatar',
        'members' => 'Miembros',
    ],
    'columns' => [
        'avatar' => 'Avatar',
        'name' => 'Nombre',
        'type' => 'Tipo',
        'description' => 'Descripción',
        'members' => 'Miembros',
        'blocked' => 'Bloqueados',
        'created_at' => 'Creado el',
    ],
    'filters' => [
        'type' => 'Tipo',
    ],
    'actions' => [
        'block_users' => 'Bloquear usuarios',
        'unblock_users' => 'Desbloquear usuarios',
        'view_blocked' => 'Ver bloqueados',
    ],
    'notifications' => [
        'blocked_ok' => 'Usuarios bloqueados',
        'unblocked_ok' => 'Usuarios desbloqueados',
    ],
];
