<?php

namespace Pms\Backend\Http;

use Pms\Backend\Controller\Controller;

/**
 * Ordnet eine Aktion dem zuständigen Bereich zu.
 *
 * Bereiche, die noch nicht umgestellt sind, werden weiterhin vom
 * Dispatcher des Altbestands (admin_action_dispatcher.php) bedient.
 */
final class Router
{
    /** @var array<string, class-string<Controller>> */
    private const ROUTES = [
        'home' => \Pms\Backend\Controller\HomeController::class,
        'bans' => \Pms\Backend\Controller\BansController::class,
        'var' => \Pms\Backend\Controller\VarController::class,
        'poll' => \Pms\Backend\Controller\PollController::class,
        'cat' => \Pms\Backend\Controller\CatController::class,
        'subcat' => \Pms\Backend\Controller\SubcatController::class,
        'menu' => \Pms\Backend\Controller\MenuController::class,
        'user' => \Pms\Backend\Controller\UserController::class,
        'config' => \Pms\Backend\Controller\ConfigController::class,
        'events' => \Pms\Backend\Controller\EventsController::class,
        'backup' => \Pms\Backend\Controller\BackupController::class,
        'activity' => \Pms\Backend\Controller\ActivityController::class,
    ];

    public static function handles(string $action): bool
    {
        return isset(self::ROUTES[$action]);
    }

    /** Führt den Bereich aus und liefert dessen HTML. */
    public static function dispatch(string $action): string
    {
        $class = self::ROUTES[$action] ?? null;
        if ($class === null) {
            return '';
        }

        /** @var Controller $controller */
        $controller = new $class();

        $error = $controller->permissionError();
        if ($error !== null) {
            return $error;
        }

        return $controller->handle();
    }
}
