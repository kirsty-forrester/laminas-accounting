<?php

/**
 * Development-only override: enable the Laminas\Db query profiler.
 *
 * Files matching *.local-development.php are merged ONLY when development mode
 * is enabled (composer development-enable), so this never affects production.
 *
 * Setting 'profiler' => true makes the adapter factory attach a
 * Laminas\Db\Adapter\Profiler\Profiler. Every executed query — SQL, bound
 * params, and elapsed time — is then retrievable via:
 *
 *     $adapter->getProfiler()->getProfiles();
 */

return [
    'db' => [
        'profiler' => true,
    ],
];
