<?php

declare(strict_types=1);

use Laminas\Log\Logger;

/**
 * Logger configuration.
 *
 * Each key under 'log' becomes a container service via laminas-log's
 * LoggerAbstractServiceFactory. Fetch it with:
 *
 *     $logger = $container->get('Accounting\Log'); // Laminas\Log\Logger
 *     $logger->info('message', ['context' => 'value']);
 */
return [
    'log' => [
        'Accounting\Log' => [
            'writers' => [
                'stream' => [
                    'name'     => 'stream',
                    'priority' => Logger::INFO, // log INFO and more severe
                    'options'  => [
                        'stream' => __DIR__ . '/../../data/logs/accounting.log',
                    ],
                ],
            ],
        ],
    ],
];
