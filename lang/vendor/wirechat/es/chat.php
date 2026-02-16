<?php

return [

    /**-------------------------
     * Chat
     *------------------------*/
    'labels' => [

        'you_replied_to_yourself' => 'Te respondiste a ti mismo',
        'participant_replied_to_you' => ':sender te respondió',
        'participant_replied_to_themself' => ':sender se respondió a sí mismo',
        'participant_replied_other_participant' => ':sender respondió a :receiver',
        'you' => 'Tú',
        'user' => 'Usuario',
        'replying_to' => 'Respondiendo a :participant',
        'replying_to_yourself' => 'Respondiéndote a ti mismo',
        'attachment' => 'Adjunto',
    ],

    'inputs' => [
        'message' => [
            'label' => 'Mensaje',
            'placeholder' => 'Escribe un mensaje',
        ],
        'media' => [
            'label' => 'Multimedia',
            'placeholder' => 'Multimedia',
        ],
        'files' => [
            'label' => 'Archivos',
            'placeholder' => 'Archivos',
        ],
    ],

    'message_groups' => [
        'today' => 'Hoy',
        'yesterday' => 'Ayer',

    ],

    'actions' => [
        'open_group_info' => [
            'label' => 'Información del Grupo',
        ],
        'open_chat_info' => [
            'label' => 'Información del Chat',
        ],
        'close_chat' => [
            'label' => 'Cerrar Chat',
        ],
        'clear_chat' => [
            'label' => 'Borrar Historial',
            'confirmation_message' => '¿Estás seguro de que quieres borrar tu historial de chat? Esto solo borrará tu chat y no afectará a otros participantes.',
        ],
        'delete_chat' => [
            'label' => 'Eliminar Chat',
            'confirmation_message' => '¿Estás seguro de que quieres eliminar este chat? Esto solo eliminará el chat de tu lado y no lo borrará para otros participantes.',
        ],

        'delete_for_everyone' => [
            'label' => 'Eliminar para todos',
            'confirmation_message' => '¿Estás seguro?',
        ],
        'delete_for_me' => [
            'label' => 'Eliminar para mí',
            'confirmation_message' => '¿Estás seguro?',
        ],
        'reply' => [
            'label' => 'Responder',
        ],

        'exit_group' => [
            'label' => 'Salir del Grupo',
            'confirmation_message' => '¿Estás seguro de que quieres salir de este grupo?',
        ],
        'upload_file' => [
            'label' => 'Archivo',
        ],
        'upload_media' => [
            'label' => 'Fotos y Videos',
        ],
    ],

    'messages' => [

        'cannot_exit_self_or_private_conversation' => 'No se puede salir de una conversación privada o propia',
        'owner_cannot_exit_conversation' => 'El propietario no puede salir de la conversación',
        'rate_limit' => '¡Demasiados intentos! Por favor, espera un momento',
        'conversation_not_found' => 'Conversación no encontrada.',
        'conversation_id_required' => 'Se requiere un ID de conversación',
        'invalid_conversation_input' => 'Entrada de conversación inválida.',
    ],

    /**-------------------------
     * Info Component
     *------------------------*/

    'info' => [
        'heading' => [
            'label' => 'Información del Chat',
        ],
        'actions' => [
            'delete_chat' => [
                'label' => 'Eliminar Chat',
                'confirmation_message' => '¿Estás seguro de que quieres eliminar este chat? Esto solo eliminará el chat de tu lado y no lo borrará para otros participantes.',
            ],
        ],
        'messages' => [
            'invalid_conversation_type_error' => 'Solo se permiten conversaciones privadas y personales',
        ],

    ],

    /**-------------------------
     * Group Folder
     *------------------------*/

    'group' => [

        // Group info component
        'info' => [
            'heading' => [
                'label' => 'Información del Grupo',
            ],
            'labels' => [
                'members' => 'Miembros',
                'add_description' => 'Añadir una descripción del grupo',
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
                'photo' => [
                    'label' => 'Foto',
                ],
            ],
            'actions' => [
                'delete_group' => [
                    'label' => 'Eliminar Grupo',
                    'confirmation_message' => '¿Estás seguro de que quieres eliminar este Grupo?',
                    'helper_text' => 'Antes de eliminar el grupo, debes eliminar a todos los miembros.',
                ],
                'add_members' => [
                    'label' => 'Añadir Miembros',
                ],
                'group_permissions' => [
                    'label' => 'Permisos del Grupo',
                ],
                'exit_group' => [
                    'label' => 'Salir del Grupo',
                    'confirmation_message' => '¿Estás seguro de que quieres salir del Grupo?',

                ],
            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Solo se permiten conversaciones de grupo',
            ],
        ],
        // Members component
        'members' => [
            'heading' => [
                'label' => 'Miembros',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Buscar',
                    'placeholder' => 'Buscar Miembros',
                ],
            ],
            'labels' => [
                'members' => 'Miembros',
                'owner' => 'Propietario',
                'admin' => 'Admin',
                'no_members_found' => 'No se encontraron miembros',
            ],
            'actions' => [
                'send_message_to_yourself' => [
                    'label' => 'Envíate un mensaje',

                ],
                'send_message_to_member' => [
                    'label' => 'Mensaje a :member',

                ],
                'dismiss_admin' => [
                    'label' => 'Quitar como Admin',
                    'confirmation_message' => '¿Estás seguro de que quieres quitar a :member como Admin?',
                ],
                'make_admin' => [
                    'label' => 'Hacer Admin',
                    'confirmation_message' => '¿Estás seguro de que quieres hacer a :member Admin?',
                ],
                'remove_from_group' => [
                    'label' => 'Eliminar',
                    'confirmation_message' => '¿Estás seguro de que quieres eliminar a :member de este Grupo?',
                ],
                'load_more' => [
                    'label' => 'Cargar más',
                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Solo se permiten conversaciones de grupo',
            ],
        ],
        // add-Members component
        'add_members' => [
            'heading' => [
                'label' => 'Añadir Miembros',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Buscar',
                    'placeholder' => 'Buscar',
                ],
            ],
            'labels' => [

            ],
            'actions' => [
                'save' => [
                    'label' => 'Guardar',

                ],

            ],
            'messages' => [
                'invalid_conversation_type_error' => 'Solo se permiten conversaciones de grupo',
                'members_limit_error' => 'Los miembros no pueden exceder :count',
                'member_already_exists' => ' Ya añadido al grupo',
            ],
        ],
        // permissions component
        'permissions' => [
            'heading' => [
                'label' => 'Permisos',
            ],
            'inputs' => [
                'search' => [
                    'label' => 'Buscar',
                    'placeholder' => 'Buscar',
                ],
            ],
            'labels' => [
                'members_can' => 'Los miembros pueden',

            ],
            'actions' => [
                'edit_group_information' => [
                    'label' => 'Editar Información del Grupo',
                    'helper_text' => 'Esto incluye el nombre, ícono y descripción',
                ],
                'send_messages' => [
                    'label' => 'Enviar Mensajes',
                ],
                'add_other_members' => [
                    'label' => 'Añadir Otros Miembros',
                ],

            ],
            'messages' => [
            ],
        ],

    ],

];
