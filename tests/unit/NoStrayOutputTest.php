<?php

namespace Pms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Keine Datei darf beim Einbinden etwas ausgeben.
 *
 * Sobald das erste Byte heraus ist, sind die Kopfzeilen gesendet. Eine
 * Weiterleitung danach kommt nicht mehr beim Browser an: Nach jedem
 * Speichern im Backend blieb deshalb eine leere Seite stehen, obwohl die
 * Aktion ausgefuehrt war. Ausloeser waren vier Tabulatoren hinter dem
 * schliessenden Tag von functions.php.
 */
final class NoStrayOutputTest extends TestCase
{
    /** Dateien, die bewusst HTML ausgeben: die Vorlagen des Backends. */
    private const TEMPLATES = '/src/backend/View/templates/';

    public function testKeineDateiGibtAusserhalbIhrerPhpBloeckeEtwasAus(): void
    {
        $root = dirname(__DIR__, 2);
        $schuldige = [];

        foreach ($this->phpFiles($root . '/src') as $file) {
            if (str_contains($file, self::TEMPLATES)) {
                continue;
            }

            $code = (string)file_get_contents($file);
            $kurz = substr($file, strlen($root) + 1);

            if (str_starts_with($code, "\xEF\xBB\xBF")) {
                $schuldige[] = $kurz . ' beginnt mit einer Byte-Order-Mark';
                continue;
            }

            $vorspann = explode('<?', $code, 2)[0];
            if (trim($vorspann) !== '') {
                $schuldige[] = $kurz . ' gibt vor dem ersten PHP-Block etwas aus';
            }

            if (!str_contains($code, '?>')) {
                continue;
            }

            // Ein schliessendes Tag am Dateiende ist die haeufigste Quelle:
            // Alles dahinter - auch ein Zeilenumbruch - geht an den Browser.
            $nachspann = (string)substr(strrchr($code, '?>') ?: '', 2);
            if ($nachspann !== '' && trim($nachspann, "\n") !== '') {
                $schuldige[] = $kurz . ' gibt hinter dem letzten "?>" '
                    . var_export($nachspann, true) . ' aus';
            }
        }

        $this->assertSame([], $schuldige, implode("\n", $schuldige));
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            $path = (string)$entry;
            if (str_ends_with($path, '.php') && !str_contains($path, '/vendor/')) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }
}
