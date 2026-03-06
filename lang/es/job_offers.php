<?php

return [
    'navigation_label' => 'Ofertas Laborales',
    'model_label' => 'Oferta Laboral',
    'plural_model_label' => 'Ofertas Laborales',

    'fields' => [
        'title' => 'Título del puesto',
        'location' => 'Ubicación',
        'contract_type' => 'Tipo de contrato',
        'salary' => 'Salario',
        'deadline' => 'Fecha límite',
        'is_active' => 'Activa',
        'description' => 'Descripción detallada',
        'requirements' => 'Requisitos',
        'benefits' => 'Beneficios',
    ],

    'help' => [
        'is_active' => 'Al activar se publica la oferta y se define la fecha de publicación.',
    ],

    'columns' => [
        'title' => 'Título',
        'location' => 'Ubicación',
        'contract_type' => 'Tipo de contrato',
        'is_active' => 'Activa',
        'deadline' => 'Fecha límite',
    ],

    'filters' => [
        'active' => 'Activa',
    ],

    'actions' => [
        'publish' => 'Publicar',
        'unpublish' => 'Despublicar',
    ],
];

