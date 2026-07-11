<?php

declare(strict_types=1);

namespace Accounting\Persistence\Logging;

use Psr\Log\AbstractLogger;

/**
 * A tiny PSR-3 logger that captures the SQL the DBAL logging middleware emits.
 * Registered as a shared service so the connection writes to the same instance
 * the debug panel reads from.
 *
 * The signature matches the installed psr/log 1.x LoggerInterface::log()
 * (untyped $message, no return type) so it stays compatible.
 */
final class QueryCollector extends AbstractLogger
{
    /** @var list<array{sql: string, params: array}> */
    private array $queries = [];

    /**
     * @param mixed  $level
     * @param mixed  $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        // The middleware logs several things (connecting, transactions…); we only
        // want records that carry an actual statement.
        if (! isset($context['sql'])) {
            return;
        }

        $this->queries[] = [
            'sql'    => (string) $context['sql'],
            'params' => $context['params'] ?? [],
        ];
    }

    /** @return list<array{sql: string, params: array}> */
    public function getQueries(): array
    {
        return $this->queries;
    }
}