<?php

return [
    'model_label' => 'Evento',
    'plural_model_label' => 'Eventos',
    'navigation_label' => 'Eventos',
    'navigation_group' => 'Comunicaciones',
    'field' => [
        'title' => 'Título',
        'calendar' => 'Calendario',
        'is_public' => 'Público',
        'is_personal' => 'Personal',
        'start' => 'Inicio',
        'end' => 'Fin',
        'all_day' => 'Todo el día',
        'color' => 'Color',
        'shared_with' => 'Compartido con',
        'description' => 'Descripción',
        'attachments' => 'Adjuntos',
    ],
    'notification' => [
        'public_new_title' => 'Nuevo Evento Público',
        'public_new_body' => ':manager ha creado el evento ":title" en el calendario ":calendar".',
        'shared_with_you_title' => 'Evento Compartido Contigo',
        'shared_with_you_body' => 'Se te ha compartido el evento ":title" del calendario ":calendar".',
    ],
];
