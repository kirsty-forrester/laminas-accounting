<?php

declare(strict_types=1);

return [
    'doctrine' => [
        'connection' => [
            // DBAL connection params. pdo_sqlite reuses the same file the
            // laminas-db adapter already points at, so both stacks read/write
            // the same database during the migration.
            'driver' => 'pdo_sqlite',
            'path'   => __DIR__ . '/../../data/accounting.sqlite',
        ],
        'mapping_paths' => [
            // Where our XML mapping files will live. Empty for now.
            __DIR__ . '/../../module/Accounting/config/orm',
        ],
        'proxy_dir'   => __DIR__ . '/../../data/cache/doctrine-proxies',
        'dev_mode'    => true, // regenerate proxies + skip metadata caching while learning
    ],
];
