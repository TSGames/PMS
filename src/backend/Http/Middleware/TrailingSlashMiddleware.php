<?php

namespace Pms\Backend\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Schneidet den Schraegstrich am Ende einer Adresse ab.
 *
 * Die Routen des Backends heissen /admin und /admin/einstellungen. Slim
 * unterscheidet das vom selben Pfad mit Schraegstrich und antwortet auf
 * /admin/ mit einem 404.
 *
 * Solche Adressen entstehen von selbst: Liegt im Webroot zufaellig ein
 * Verzeichnis, das wie eine Route heisst, schickt Apaches mod_dir eine
 * dauerhafte Weiterleitung dorthin - und die merkt sich jeder Browser,
 * auch nachdem der Server laengst in Ordnung ist. Genauso tippen Leute
 * einen Schraegstrich mit oder ein Lesezeichen traegt einen.
 *
 * Hier wird daraus eine Weiterleitung auf die Adresse ohne Schraegstrich.
 */
final class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Die Wurzel selbst behaelt ihren Schraegstrich
        if ($path === '/' || !str_ends_with($path, '/')) {
            return $handler->handle($request);
        }

        $kurz = rtrim($path, '/');
        if ($kurz === '') {
            return $handler->handle($request);
        }

        // Ein abgeschicktes Formular darf nicht verlorengehen: Bei POST
        // wird nicht weitergeleitet, sondern gleich richtig zugestellt.
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request->withUri($uri->withPath($kurz)));
        }

        // Der Handler wird gar nicht erst gerufen: Er kennt die Adresse mit
        // Schraegstrich nicht und wuerde einen 404 werfen, bevor die
        // Weiterleitung zustande kaeme.
        return $this->responseFactory
            ->createResponse(301)
            ->withHeader('Location', (string)$uri->withPath($kurz));
    }
}
