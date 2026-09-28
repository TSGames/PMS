<?php

namespace Pms\Backend\Http;

use Pms\Backend\Push\PushService;
use Pms\Backend\Report\WeeklyReport;
use Pms\Support\Auth;
use Pms\Support\Csrf;
use Pms\Support\Request;

/**
 * Schnittstelle der Push-Einstellungen auf der Seite "Wochenbericht"
 * (js/admin-push.js): ein Gerät an- oder abmelden, Testnachricht senden.
 *
 * Liefert JSON und läuft deshalb vor jeder HTML-Ausgabe.
 */
final class PushEndpoint
{
    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::isLoggedIn()) {
            self::send(['error' => 'Authentifizierung erforderlich'], 401);
        }
        if (!Csrf::check()) {
            self::send(['error' => 'Ungültiges Sicherheitstoken'], 403);
        }

        match (Request::string('do')) {
            'subscribe' => self::subscribe(),
            'unsubscribe' => self::unsubscribe(),
            'test' => self::test(),
            default => self::send(['error' => 'Unbekannte Aktion'], 400),
        };
    }

    private static function subscribe(): never
    {
        $ok = PushService::subscribe(
            Auth::userId(),
            Request::string('endpoint'),
            Request::string('p256dh'),
            Request::string('auth')
        );

        $ok
            ? self::send(['ok' => true, 'devices' => PushService::countForUser(Auth::userId())])
            : self::send(['error' => 'Das Abonnement ist unvollständig.'], 400);
    }

    private static function unsubscribe(): never
    {
        PushService::unsubscribe(Auth::userId(), Request::string('endpoint'));
        self::send(['ok' => true, 'devices' => PushService::countForUser(Auth::userId())]);
    }

    /** Die laufende Woche als Nachricht an die eigenen Geräte. */
    private static function test(): never
    {
        $summary = WeeklyReport::summary(WeeklyReport::preview(time()));
        $result = PushService::sendToUser(Auth::userId(), [
            'title' => 'Test: ' . $summary['title'],
            'body' => 'Laufende Woche bisher: ' . $summary['body'],
            'url' => Routes::path('weekly_report'),
        ]);

        self::send(['ok' => $result['sent'] > 0] + $result);
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
