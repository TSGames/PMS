<?php

namespace Pms\Backend\Http\Middleware;

use Pms\Backend\Http\Kernel;
use Pms\Backend\Http\Routes;
use Pms\Support\Csrf;
use Pms\Support\Flash;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Weist verändernde Anfragen ohne gültiges Sicherheitstoken ab, bevor ein
 * Controller sie zu sehen bekommt.
 *
 * Die Controller prüfen das Token zusätzlich selbst; diese Schicht stellt
 * sicher, dass keine neue Stelle das Prüfen vergessen kann.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || Csrf::check()) {
            return $handler->handle($request);
        }

        // Die JSON-Schnittstellen prüfen selbst und antworten mit JSON; eine
        // Weiterleitung auf eine HTML-Seite wäre für sie unbrauchbar
        if (Kernel::isEndpoint(Routes::currentAction())) {
            return $handler->handle($request);
        }

        Flash::error('Die Sitzung ist abgelaufen oder die Anfrage stammt nicht von dieser Seite. Bitte erneut versuchen.');

        // Der Controller läuft bewusst nicht an: Die Anfrage bleibt ohne Wirkung.
        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', Routes::path(Routes::currentAction()));
    }
}
