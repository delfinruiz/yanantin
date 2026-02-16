<?php

return [

    // new-chat component
    'chat' => [
        'labels' => [
            'heading' => ' Nuevo Chat',
            'you' => 'Tú',

        ],

        'inputs' => [
            'search' => [
                'label' => 'Buscar Conversaciones',
                'placeholder' => 'Buscar',
            ],
        ],

        'actions' => [
            'new_group' => [
                'label' => 'Nuevo grupo',
            ],

        ],

        'messages' => [

            'empty_search_result' => 'No se encontraron usuarios que coincidan con tu búsqueda.',
        ],
    ],

    // new-group component
    'group' => [
        'labels' => [
            'heading' => ' Nuevo Chat',
            'add_members' => ' Añadir Miembros',

        ],

        'inputs' => [
            'name' => [
                'label' => 'Nombre del Grupo',
                'placeholder' => 'Ingresa el Nombre',
            ],
            'description' => [
                'label' => 'Descripción',
                'placeholder' => 'Opcional',
            ],
            'search' => [
                'label' => 'Buscar',
                'placeholder' => 'Buscar',
            ],
            'photo' => [
                'label' => 'Foto',
            ],
        ],

        'actions' => [
            'cancel' => [
                'label' => 'Cancelar',
            ],
            'next' => [
                'label' => 'Siguiente',
            ],
            'create' => [
                'label' => 'Crear',
            ],

        ],

        'messages' => [
            'members_limit_error' => 'Los miembros no pueden exceder :count',
            'empty_search_result' => 'No se encontraron usuarios que coincidan con tu búsqueda.',
        ],
    ],

];
