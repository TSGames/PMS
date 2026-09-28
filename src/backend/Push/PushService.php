<?php

namespace Pms\Backend\Push;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Pms\Data\Db;
use Pms\Support\Auth;

/**
 * Push-Nachrichten an die Geräte der Redaktion (Web Push mit VAPID).
 *
 * Ein Gerät abonniert im Backend auf der Seite "Wochenbericht"; hier
 * liegen die Abos und der Versand. Das Schlüsselpaar entsteht beim ersten
 * Gebrauch und liegt neben der Datenbank im dauerhaften Volume - ein neues
 * Paar würde alle bestehenden Abos ungültig machen.
 */
final class PushService
{
    private const KEY_FILE = '/var/db/vapid.json';

    /** Wie lange der Push-Dienst eine Nachricht für ein ausgeschaltetes Gerät aufhebt. */
    private const TTL = 2 * 86400;

    /** @return array{publicKey: string, privateKey: string} */
    public static function keys(): array
    {
        $stored = is_file(self::KEY_FILE) ? json_decode((string)file_get_contents(self::KEY_FILE), true) : null;
        if (is_array($stored) && isset($stored['publicKey'], $stored['privateKey'])) {
            return ['publicKey' => (string)$stored['publicKey'], 'privateKey' => (string)$stored['privateKey']];
        }

        $keys = VAPID::createVapidKeys();
        file_put_contents(self::KEY_FILE, (string)json_encode($keys));
        @chmod(self::KEY_FILE, 0600);

        return ['publicKey' => $keys['publicKey'], 'privateKey' => $keys['privateKey']];
    }

    public static function publicKey(): string
    {
        return self::keys()['publicKey'];
    }

    /** Speichert das Abo eines Geräts; ein schon bekanntes wird übernommen. */
    public static function subscribe(int $userId, string $endpoint, string $p256dh, string $auth): bool
    {
        if (!str_starts_with($endpoint, 'https://') || $p256dh === '' || $auth === '') {
            return false;
        }

        Db::execute('DELETE FROM ' . Db::table('push_subscriptions') . ' WHERE endpoint = :endpoint', ['endpoint' => $endpoint]);

        return Db::insert('push_subscriptions', [
            'user' => $userId,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'created' => time(),
        ]) > 0;
    }

    public static function unsubscribe(int $userId, string $endpoint): void
    {
        Db::execute(
            'DELETE FROM ' . Db::table('push_subscriptions') . ' WHERE endpoint = :endpoint AND user = :user',
            ['endpoint' => $endpoint, 'user' => $userId]
        );
    }

    public static function countForUser(int $userId): int
    {
        return Db::count('push_subscriptions', 'user = :user', ['user' => $userId]);
    }

    /**
     * An alle Geräte von Benutzern, die das Backend noch betreten dürfen -
     * wer gesperrt oder herabgestuft wurde, bekommt nichts mehr.
     *
     * @param array{title: string, body: string, url: string} $payload
     * @return array{sent: int, failed: int, removed: int}
     */
    public static function sendToAll(array $payload): array
    {
        return self::send(Db::select(
            'SELECT s.* FROM ' . Db::table('push_subscriptions') . ' s'
            . ' JOIN ' . Db::table('user') . ' u ON u.id = s.user'
            . ' WHERE u.active = 1 AND u.typ >= :minimum',
            ['minimum' => Auth::BACKEND_MINIMUM]
        ), $payload);
    }

    /**
     * @param array{title: string, body: string, url: string} $payload
     * @return array{sent: int, failed: int, removed: int}
     */
    public static function sendToUser(int $userId, array $payload): array
    {
        return self::send(Db::select(
            'SELECT * FROM ' . Db::table('push_subscriptions') . ' WHERE user = :user',
            ['user' => $userId]
        ), $payload);
    }

    /**
     * @param list<object> $subscriptions
     * @param array{title: string, body: string, url: string} $payload
     * @return array{sent: int, failed: int, removed: int}
     */
    private static function send(array $subscriptions, array $payload): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0];
        if ($subscriptions === []) {
            return $result;
        }

        $keys = self::keys();
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => self::subject(),
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey'],
            ],
        ], ['TTL' => self::TTL], 15);

        $message = (string)json_encode($payload);
        foreach ($subscriptions as $row) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => (string)$row->endpoint,
                'publicKey' => (string)$row->p256dh,
                'authToken' => (string)$row->auth,
                'contentEncoding' => 'aes128gcm',
            ]), $message);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $result['sent']++;
                continue;
            }
            $result['failed']++;
            // Abgemeldet oder App deinstalliert: Der Push-Dienst kennt das Abo
            // nicht mehr, weitere Versuche wären sinnlos
            if ($report->isSubscriptionExpired()) {
                Db::execute(
                    'DELETE FROM ' . Db::table('push_subscriptions') . ' WHERE endpoint = :endpoint',
                    ['endpoint' => $report->getEndpoint()]
                );
                $result['removed']++;
            } else {
                error_log('PMS Push fehlgeschlagen: ' . $report->getReason());
            }
        }

        return $result;
    }

    /** Kontakt für die Push-Dienste - Pflichtangabe von VAPID. */
    private static function subject(): string
    {
        $mail = (string)Db::value('SELECT mail FROM ' . Db::table('config') . ' WHERE id = 1');

        return filter_var($mail, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $mail : 'mailto:webmaster@localhost';
    }
}
