<?php

return [
    'config' => [],
    'entities' => [
        'User' => [
            [
                'id' => '1',
                'isAdmin' => true,
                'type' => 'admin',
                'userName' => 'admin',
                'password' => '1',
                'salutationName' => '',
                'firstName' => '',
                'lastName' => 'Admin',
                'title' => '',
                'emailAddress' => 'demo@espocrm.com',
                'phoneNumberData' => [
                    (object) [
                        'phoneNumber' => '111',
                        'primary' => true,
                        'type' => 'Office',
                    ],
                ],
            ],
        ],
    ],
];
