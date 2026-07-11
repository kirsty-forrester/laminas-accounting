<?php

declare(strict_types=1);

namespace Application;

use Accounting\Persistence\Logging\QueryCollector;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;

use function count;
use function htmlspecialchars;
use function json_encode;
use function sprintf;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $application = $e->getApplication();
        $services    = $application->getServiceManager();

        // Dev helper: append the request's SQL to the page when the query string
        // contains ?debug-sql. Queries are gathered by the Doctrine DBAL logging
        // middleware, which is only attached in development mode (see
        // EntityManagerFactory), so this is free on normal requests and inert in
        // production.
        //
        // Priority -9000 runs just before SendResponseListener (-10000), so the
        // content is modified before the response is flushed to the client.
        $application->getEventManager()->attach(
            MvcEvent::EVENT_FINISH,
            static function (MvcEvent $event) use ($services): void {
                $request = $event->getRequest();
                if (! $request instanceof HttpRequest) {
                    return;
                }

                // Present-but-empty (?debug-sql) yields '', which is still !== null.
                if (null === $request->getQuery('debug-sql', null)) {
                    return;
                }

                // Inert in production: the collector only receives queries when
                // the dev-mode middleware is attached (see EntityManagerFactory).
                $config = $services->get('config');
                if (empty($config['doctrine']['dev_mode'])) {
                    return;
                }

                if (! $services->has(QueryCollector::class)) {
                    return;
                }

                $queries = $services->get(QueryCollector::class)->getQueries();

                $response = $event->getResponse();
                if (! $response instanceof HttpResponse) {
                    return;
                }

                $response->setContent(
                    $response->getContent() . self::renderSqlDebug($queries)
                );
            },
            -9000
        );
    }

    /**
     * Render the collected queries as a fixed debug panel.
     *
     * @param list<array{sql: string, params: array}> $queries
     */
    private static function renderSqlDebug(array $queries): string
    {
        $rows = '';

        foreach ($queries as $i => $query) {
            $rows .= sprintf(
                '<tr><td style="padding:2px 8px;vertical-align:top;">%d</td>'
                . '<td style="padding:2px 8px;"><code>%s</code></td>'
                . '<td style="padding:2px 8px;">%s</td></tr>',
                $i + 1,
                htmlspecialchars($query['sql'], ENT_QUOTES),
                htmlspecialchars(json_encode($query['params']) ?: '[]', ENT_QUOTES),
            );
        }

        if ('' === $rows) {
            $rows = '<tr><td colspan="3" style="padding:4px 8px;"><em>No queries recorded '
                . 'for this request.</em></td></tr>';
        }

        return sprintf(
            '<div style="position:fixed;bottom:0;left:0;right:0;max-height:40vh;overflow:auto;'
            . 'background:#1d1f21;color:#c5c8c6;font:12px/1.6 monospace;z-index:99999;'
            . 'padding:10px 14px;box-shadow:0 -2px 10px rgba(0,0,0,.5);">'
            . '<strong style="color:#81a2be;">SQL debug</strong> &mdash; %d quer%s'
            . '<table style="width:100%%;border-collapse:collapse;margin-top:6px;">'
            . '<thead><tr style="text-align:left;color:#b5bd68;">'
            . '<th style="padding:2px 8px;">#</th><th style="padding:2px 8px;">SQL</th>'
            . '<th style="padding:2px 8px;">Params</th>'
            . '</tr></thead><tbody>%s</tbody></table></div>',
            count($queries),
            count($queries) === 1 ? 'y' : 'ies',
            $rows
        );
    }
}
