<?php

namespace Pms\Frontend\Http;

use Pms\Data\Db;

/**
 * Pruefung, ob die anfragende Adresse gesperrt ist.
 *
 * Ist sie das, sieht der Besucher nur noch die dafuer vorgesehene
 * Spezialseite. Der Altbestand loeste das mit unset($_GET) und unset($_POST)
 * mitten in der Schleife - hier faellt stattdessen ein Ziel heraus, das
 * ausschliesslich auf diese Seite zeigt.
 */
final class Ban
{
    /** Die Spezialseite, die gesperrten Adressen gezeigt wird. */
    private const SPECIAL_BANNED = 5;

    private function __construct(
        public readonly string $ip,
        public readonly string $reason,
        public readonly string $time,
        public readonly int $item,
    ) {
    }

    /**
     * Sucht einen Eintrag, der auf die anfragende Adresse passt.
     *
     * Die Sperrliste enthaelt auch Teiladressen ("192.168."), deshalb wird
     * auf Teilzeichenketten verglichen und nicht auf Gleichheit.
     */
    public static function check(string $remoteAddress): ?self
    {
        if ($remoteAddress === '') {
            return null;
        }

        foreach (Db::select('SELECT ip, reason, time FROM ' . Db::table('bans') . ' ORDER BY id') as $ban) {
            $pattern = (string)($ban->ip ?? '');
            if ($pattern === '' || stripos($remoteAddress, $pattern) === false) {
                continue;
            }

            return new self(
                ip: $remoteAddress,
                reason: self::reason((string)($ban->reason ?? '')),
                time: self::remaining((int)($ban->time ?? 0)),
                item: self::bannedPage(),
            );
        }

        return null;
    }

    /** Das einzige Ziel, das einer gesperrten Adresse noch offensteht. */
    public function target(): Target
    {
        return new Target(item: $this->item);
    }

    private static function reason(string $reason): string
    {
        $reason = trim(function_exists('def') ? (string)def($reason) : $reason);
        return $reason === '' ? (string)language('BAN_NO_REASON') : $reason;
    }

    /** Verbleibende Dauer in Tagen, oder der Hinweis auf eine Dauersperre. */
    private static function remaining(int $until): string
    {
        $days = ban_time($until);
        if ($days === language('BAN_UNLIMITED')) {
            return (string)$days;
        }
        return $days . ((int)$days === 1 ? ' Tag' : ' Tage');
    }

    /** Kennung der Spezialseite; ohne sie bleibt die Seite leer. */
    private static function bannedPage(): int
    {
        $id = Db::value('SELECT id FROM ' . Db::table('item') . ' WHERE special = ? ORDER BY id LIMIT 1', [self::SPECIAL_BANNED]);
        return is_numeric($id) ? (int)$id : -1;
    }
}
