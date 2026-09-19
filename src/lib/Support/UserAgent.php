<?php

namespace Pms\Support;

/**
 * Macht eine User-Agent-Zeichenkette lesbar.
 *
 * Der Website-Status zeigte bisher mehrere hundert Zeilen roher Kennungen
 * wie "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML,
 * like Gecko) Chrome/120.0.0.0 Safari/537.36". Daraus wird hier
 * "Chrome 120 auf Windows".
 *
 * Die Reihenfolge der Muster ist wesentlich: Jeder Browser gibt sich
 * zusätzlich als etwas anderes aus. Edge nennt sich auch Chrome, Chrome auch
 * Safari - deshalb wird von speziell nach allgemein geprüft.
 */
final class UserAgent
{
    /** @var list<array{0: string, 1: string}> Suchbegriff => Anzeigename */
    private const BROWSERS = [
        ['Edg/', 'Edge'],
        ['OPR/', 'Opera'],
        ['SamsungBrowser/', 'Samsung Internet'],
        ['Firefox/', 'Firefox'],
        ['Chrome/', 'Chrome'],
        ['Version/', 'Safari'],
        ['MSIE ', 'Internet Explorer'],
    ];

    /** @var array<string, string> Suchbegriff => Betriebssystem */
    private const SYSTEMS = [
        'Windows NT 10' => 'Windows 10/11',
        'Windows' => 'Windows',
        'Android' => 'Android',
        'iPhone' => 'iOS',
        'iPad' => 'iPadOS',
        'Mac OS X' => 'macOS',
        'CrOS' => 'ChromeOS',
        'Linux' => 'Linux',
    ];

    /** @var list<string> Kennungen, die auf einen Suchmaschinen-Besuch deuten */
    private const BOTS = ['bot', 'crawler', 'spider', 'slurp', 'facebookexternalhit', 'preview'];

    /** Kurzform wie "Chrome 120 auf Windows 10/11". */
    public static function describe(?string $agent): string
    {
        $agent = trim((string)$agent);
        if ($agent === '') {
            return 'Unbekannt';
        }

        if (self::isBot($agent)) {
            return self::botName($agent);
        }

        $browser = self::browser($agent);
        $system = self::system($agent);

        if ($browser === '' && $system === '') {
            // Nichts erkannt: lieber ein gekürztes Original als gar nichts
            return mb_strlen($agent) > 60 ? mb_substr($agent, 0, 57) . '…' : $agent;
        }
        if ($browser === '') {
            return $system;
        }

        return $system === '' ? $browser : $browser . ' auf ' . $system;
    }

    /** Stammt der Zugriff von einer Suchmaschine? */
    public static function isBot(?string $agent): bool
    {
        $agent = mb_strtolower(trim((string)$agent));
        foreach (self::BOTS as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function browser(string $agent): string
    {
        foreach (self::BROWSERS as [$needle, $label]) {
            if (!str_contains($agent, $needle)) {
                continue;
            }
            $version = self::versionAfter($agent, $needle);
            return $version === '' ? $label : $label . ' ' . $version;
        }
        return '';
    }

    private static function system(string $agent): string
    {
        foreach (self::SYSTEMS as $needle => $label) {
            if (str_contains($agent, $needle)) {
                return $label;
            }
        }
        return '';
    }

    /** Die Hauptversionsnummer hinter einem Suchbegriff. */
    private static function versionAfter(string $agent, string $needle): string
    {
        $rest = substr($agent, strpos($agent, $needle) + strlen($needle));
        return preg_match('/^(\d+)/', $rest, $matches) === 1 ? $matches[1] : '';
    }

    /** Der Name des Bots, sofern er sich nennt. */
    private static function botName(string $agent): string
    {
        if (preg_match('#([A-Za-z][A-Za-z0-9\-]*(?:bot|crawler|spider|Slurp))#i', $agent, $matches) === 1) {
            return $matches[1];
        }
        return 'Suchmaschine';
    }
}
