<?php

return [
    'navigation_label' => 'Mis Ausencias',
    'navigation_group' => 'Mis Aplicaciones',
    'model_label' => 'Mi Ausencia',
    'plural_model_label' => 'Mis Ausencias',
    'columns' => [
        'type' => 'Tipo',
        'start_date' => 'Desde',
        'end_date' => 'Hasta',
        'days' => 'Días',
        'status' => 'Estado',
    ],
    'status' => [
        'pending' => 'Pendiente',
        'approved_supervisor' => 'Aprobado Sup.',
        'approved_hr' => 'Aprobado Final',
        'rejected' => 'Rechazado',
    ],
    'notifications' => [
        'cannot_create' => [
            'title' => 'No se puede crear la solicitud',
        ],
        'no_department' => [
            'body' => 'No perteneces a ningún departamento, por lo que no puedes enviar solicitudes.',
        ],
        'no_supervisor' => [
            'body' => 'El departamento al que perteneces no tiene un supervisor asignado para autorizar la solicitud.',
        ],
        'cannot_edit' => [
            'title' => 'No se puede editar',
        ],
        'only_pending' => [
            'body' => 'Solo se pueden editar solicitudes pendientes.',
        ],
    ],
];
