<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Die mysqli-Namen, die Inhalte aus der Datenbank rufen.
 *
 * PMS lief früher auf MySQL. Redaktionelle Inhalte können deshalb
 * [php]-Blöcke enthalten, die mysqli_fetch_object() und Verwandte rufen —
 * make_dynamic() führt sie per eval() aus. Diese Aufrufe stehen in der
 * Datenbank, nicht im Quelltext.
 *
 * Genau daran ist der Umbau einmal gescheitert: Die Funktionen wurden
 * entfernt, weil sie im Code niemand mehr rief, und auf einem System mit
 * eigenen Inhalten brach daraufhin die Seite ab
 * (BEFUNDE B19). Eine Suche im Quelltext hätte das nie gezeigt.
 */
final class LegacyDatabaseFunctionsTest extends TestCase
{
    private const GLOBAL_FILE = __DIR__ . '/../../src/functions_global.php';

    /**
     * Namen, die in Altinhalten vorkommen können und deshalb belegt sein
     * müssen, solange die mysqli-Erweiterung fehlt.
     *
     * @return list<array{string}>
     */
    public static function legacyNames(): array
    {
        return [
            ['mysqli_fetch_object'],
            ['mysqli_num_rows'],
            ['mysqli_fetch_assoc'],
            ['mysqli_fetch_array'],
            ['mysqli_free_result'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('legacyNames')]
    public function testJederAltnameWirdBereitgestellt(string $name): void
    {
        $quelltext = (string)file_get_contents(self::GLOBAL_FILE);

        self::assertStringContainsString(
            'function ' . $name . '(',
            $quelltext,
            $name . ' fehlt - Inhalte mit [php]-Blöcken brechen dann ab'
        );
    }

    public function testDieAltnamenWerdenNurOhneMysqliBelegt(): void
    {
        $quelltext = (string)file_get_contents(self::GLOBAL_FILE);

        // Ist die Erweiterung geladen, gibt es die echten Funktionen. Sie zu
        // überschreiben bricht das Laden der gesamten Website ab.
        self::assertMatchesRegularExpression(
            "/if \(!extension_loaded\('mysqli'\)\) \{/",
            $quelltext,
            'die Altnamen müssen hinter einer Prüfung auf die Erweiterung stehen'
        );

        $position = strpos($quelltext, "if (!extension_loaded('mysqli'))");
        self::assertIsInt($position);

        foreach (self::legacyNames() as [$name]) {
            self::assertGreaterThan(
                $position,
                strpos($quelltext, 'function ' . $name . '('),
                $name . ' steht außerhalb der Prüfung'
            );
        }
    }

    public function testNeuerCodeHatEigeneNamen(): void
    {
        $quelltext = (string)file_get_contents(self::GLOBAL_FILE);

        foreach (['pms_fetch_object', 'pms_num_rows'] as $name) {
            self::assertStringContainsString('function ' . $name . '(', $quelltext);
            self::assertLessThan(
                strpos($quelltext, "if (!extension_loaded('mysqli'))"),
                strpos($quelltext, 'function ' . $name . '('),
                $name . ' soll immer da sein, nicht nur ohne mysqli'
            );
        }
    }
}
