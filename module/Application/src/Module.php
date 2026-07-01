<?php

declare(strict_types=1);

namespace Application;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Traversable;

use function count;
use function htmlspecialchars;
use function is_object;
use function iterator_to_array;
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

        // Dev helper: append the request's profiled SQL to the page when the
        // query string contains ?debug-sql. This is a no-op unless BOTH the
        // debug-sql param is present AND the Laminas\Db profiler is enabled
        // (which only happens in development mode), so it's free on normal
        // requests and inert in production.
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

                if (! $services->has(AdapterInterface::class)) {
                    return;
                }

                $profiler = $services->get(AdapterInterface::class)->getProfiler();
                if (null === $profiler) {
                    return; // profiler not enabled — e.g. production
                }

                $response = $event->getResponse();
                if (! $response instanceof HttpResponse) {
                    return;
                }

                $response->setContent(
                    $response->getContent() . self::renderSqlDebug($profiler->getProfiles())
                );
            },
            -9000
        );
    }

    /**
     * Render the collected query profiles as a fixed debug panel.
     *
     * @param array<int, array{sql: string, parameters: mixed, elapse: float|null}> $profiles
     */
    private static function renderSqlDebug(array $profiles): string
    {
        $rows  = '';
        $total = 0.0;

        foreach ($profiles as $i => $profile) {
            $total += (float) $profile['elapse'];

            $params = $profile['parameters'];
            if (is_object($params) && $params instanceof Traversable) {
                $params = iterator_to_array($params);
            }

            $rows .= sprintf(
                '<tr><td style="padding:2px 8px;vertical-align:top;">%d</td>'
                . '<td style="padding:2px 8px;"><code>%s</code></td>'
                . '<td style="padding:2px 8px;">%s</td>'
                . '<td style="padding:2px 8px;white-space:nowrap;">%.4f s</td></tr>',
                $i + 1,
                htmlspecialchars((string) $profile['sql'], ENT_QUOTES),
                htmlspecialchars(json_encode($params) ?: '[]', ENT_QUOTES),
                (float) $profile['elapse']
            );
        }

        if ('' === $rows) {
            $rows = '<tr><td colspan="4" style="padding:4px 8px;"><em>No queries recorded '
                . 'for this request.</em></td></tr>';
        }

        return sprintf(
            '<div style="position:fixed;bottom:0;left:0;right:0;max-height:40vh;overflow:auto;'
            . 'background:#1d1f21;color:#c5c8c6;font:12px/1.6 monospace;z-index:99999;'
            . 'padding:10px 14px;box-shadow:0 -2px 10px rgba(0,0,0,.5);">'
            . '<strong style="color:#81a2be;">SQL debug</strong> &mdash; %d quer%s, %.4f s total'
            . '<table style="width:100%%;border-collapse:collapse;margin-top:6px;">'
            . '<thead><tr style="text-align:left;color:#b5bd68;">'
            . '<th style="padding:2px 8px;">#</th><th style="padding:2px 8px;">SQL</th>'
            . '<th style="padding:2px 8px;">Params</th><th style="padding:2px 8px;">Time</th>'
            . '</tr></thead><tbody>%s</tbody></table></div>',
            count($profiles),
            count($profiles) === 1 ? 'y' : 'ies',
            $total,
            $rows
        );
    }
}
