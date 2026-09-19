<?php

namespace Pms\Backend\Http;

use Pms\Backend\Controller\Controller;
use Pms\Backend\Http\Middleware\CsrfMiddleware;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Layout;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Baut die Anwendung auf und beantwortet die Anfrage.
 *
 * Slim übernimmt Routing und Antwort; die Bereiche selbst bleiben einfache
 * Controller, die HTML zurückgeben.
 */
final class Kernel
{
    /** @var array<string, class-string<Controller>> */
    private const CONTROLLERS = [
        'home' => \Pms\Backend\Controller\HomeController::class,
        'config' => \Pms\Backend\Controller\ConfigController::class,
        'menu' => \Pms\Backend\Controller\MenuController::class,
        'user' => \Pms\Backend\Controller\UserController::class,
        'cat' => \Pms\Backend\Controller\CatController::class,
        'subcat' => \Pms\Backend\Controller\SubcatController::class,
        'item' => \Pms\Backend\Controller\ItemController::class,
        'item_restore' => \Pms\Backend\Controller\ItemRestoreController::class,
        'item_recover' => \Pms\Backend\Controller\ItemRecoverController::class,
        'var' => \Pms\Backend\Controller\VarController::class,
        'poll' => \Pms\Backend\Controller\PollController::class,
        'bans' => \Pms\Backend\Controller\BansController::class,
        'events' => \Pms\Backend\Controller\EventsController::class,
        'backup' => \Pms\Backend\Controller\BackupController::class,
        'activity' => \Pms\Backend\Controller\ActivityController::class,
    ];

    /**
     * @param list<array{action: string, label: string}> $modules Verfügbare Module
     * @param string $moduleContent Ausgabe des aufgerufenen Moduls
     */
    public static function run(array $modules = [], string $moduleContent = ''): void
    {
        $app = AppFactory::create();
        $app->setBasePath(Routes::basePath());

        self::registerRoutes($app, $modules, $moduleContent);

        $app->add(new CsrfMiddleware($app->getResponseFactory()));
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware((bool)ini_get('display_errors'), true, true);

        $app->run();
    }

    /**
     * Schnittstellen, die JSON liefern: Aktion => Klasse mit handle().
     *
     * @var array<string, class-string>
     */
    private const ENDPOINTS = [
        'xlsx_import_ajax' => XlsxEndpoint::class,
        'crop_image_ajax' => CropEndpoint::class,
        'options_ajax' => OptionsEndpoint::class,
        'image_ajax' => ImageEndpoint::class,
    ];

    /** Beantwortet diese Aktion eine Schnittstelle statt einer Seite? */
    public static function isEndpoint(string $action): bool
    {
        return isset(self::ENDPOINTS[$action]);
    }

    /** @param list<array{action: string, label: string}> $modules */
    private static function registerRoutes(App $app, array $modules, string $moduleContent): void
    {
        $methods = ['GET', 'POST'];

        // Schnittstellen ohne Seitenausgabe
        foreach (self::ENDPOINTS as $action => $endpoint) {
            $app->map($methods, Routes::all()[$action], static function (ServerRequestInterface $request, ResponseInterface $response) use ($action, $endpoint) {
                Request::bind($request, $action);
                $endpoint::handle();
                return $response;
            });
        }

        // Abmelden und offene Vorgänge wurden bereits beim Start verarbeitet
        foreach (['logout', 'load_last', 'update'] as $action) {
            $app->map($methods, Routes::all()[$action], static fn(ServerRequestInterface $request, ResponseInterface $response)
                => self::page($request, $response, 'home', $modules, $moduleContent));
        }

        // Module
        $app->map($methods, Routes::MODULE_PATH, static function (ServerRequestInterface $request, ResponseInterface $response) use ($modules, $moduleContent) {
            Request::bind($request, '');
            $response->getBody()->write(self::render($moduleContent, '', $modules));
            return $response;
        });

        // Die Bereiche des Backends
        foreach (self::CONTROLLERS as $action => $class) {
            $app->map($methods, Routes::all()[$action], static fn(ServerRequestInterface $request, ResponseInterface $response)
                => self::page($request, $response, $action, $modules, $moduleContent));
        }

        // Frühere Adressform: GET wird umgeleitet, POST direkt beantwortet,
        // damit abgeschickte Formulare alter Seiten nicht verloren gehen.
        $app->map($methods, Routes::LEGACY_PATH, static function (ServerRequestInterface $request, ResponseInterface $response) use ($modules, $moduleContent) {
            $action = Routes::currentAction();

            if ($request->getMethod() === 'GET') {
                $query = $request->getQueryParams();
                unset($query['action']);
                $target = Routes::path($action) . ($query === [] ? '' : '?' . http_build_query($query));
                return $response->withStatus(301)->withHeader('Location', $target);
            }

            // Eine Schnittstelle muss auch über die alte Adresse JSON liefern
            // und nicht die Startseite - sonst bekommt das Skript HTML zurück
            if (self::isEndpoint($action)) {
                Request::bind($request, $action);
                self::ENDPOINTS[$action]::handle();
                return $response;
            }

            return self::page($request, $response, $action, $modules, $moduleContent);
        });
    }

    /**
     * Führt einen Bereich aus und gibt die fertige Seite zurück.
     *
     * @param list<array{action: string, label: string}> $modules
     */
    private static function page(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $action,
        array $modules,
        string $moduleContent
    ): ResponseInterface {
        Request::bind($request, $action);

        if (!Auth::isLoggedIn()) {
            // Ohne gültige Sitzung wird nichts verarbeitet
            if ($request->getMethod() === 'POST' && !Request::submitted('login')) {
                \Pms\Backend\Support\Flash::error(
                    'Aus Sicherheitsgründen wurde die Sitzung beendet.<br>'
                    . 'Bitte geben Sie Ihre Zugangsdaten erneut ein'
                );
            }
            // Die Anmeldemaske ist eine gewöhnliche Seite, kein Fehler
            $response->getBody()->write(self::renderLogin());
            return $response;
        }

        $module = Routes::currentModule();
        if ($module !== '') {
            $response->getBody()->write(self::render($moduleContent, '', $modules));
            return $response;
        }

        try {
            $content = self::content($action);
        } catch (RedirectSignal $signal) {
            return $response->withStatus($signal->status())->withHeader('Location', $signal->target());
        }

        $response->getBody()->write(self::render($content, $action, $modules));
        return $response;
    }

    /** HTML des Inhaltsbereichs einer Aktion. */
    private static function content(string $action): string
    {
        $blocking = UpdateGate::render();
        if ($blocking !== null) {
            return $blocking;
        }

        $class = self::CONTROLLERS[$action] ?? self::CONTROLLERS['home'];
        /** @var Controller $controller */
        $controller = new $class();

        return $controller->permissionError() ?? $controller->handle();
    }

    /** @param list<array{action: string, label: string}> $modules */
    private static function render(string $content, string $action, array $modules): string
    {
        ob_start();
        Layout::render($content, $action, $modules);
        return (string)ob_get_clean();
    }

    private static function renderLogin(): string
    {
        $rememberedName = '';
        if (!empty($_COOKIE['login_id'])) {
            $rememberedName = (string)from_db('user', (int)$_COOKIE['login_id'], 'name');
        }

        ob_start();
        Layout::renderLogin($rememberedName);
        return (string)ob_get_clean();
    }
}
