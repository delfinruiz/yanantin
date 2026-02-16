<?php

return [
    'title' => 'Main Dashboard',
    'navigation_label' => 'Home',
    'stats' => [
        'pay' => [
            'label' => 'Pay',
            'description' => 'Accounts Payable in the current year',
        ],
        'send' => [
            'label' => 'Send',
            'description' => 'Packages to send in the current year',
        ],
        'do' => [
            'label' => 'Do',
            'description' => 'Pending tasks to do in the current year',
        ],
    ],
    'chart' => [
        'financial_status' => 'Annual Financial Status',
        'income' => 'Income',
        'expenses' => 'Expenses',
    ],
];
