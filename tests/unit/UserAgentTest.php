<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Support\UserAgent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Support\UserAgent - macht Browser-Kennungen lesbar.
 */
final class UserAgentTest extends TestCase
{
    #[DataProvider('browsers')]
    public function testBeschreibtBekannteBrowser(string $agent, string $expected): void
    {
        self::assertSame($expected, UserAgent::describe($agent));
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function browsers(): iterable
    {
        yield 'Chrome unter Windows' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Chrome 120 auf Windows 10/11',
        ];
        yield 'Firefox unter Linux' => [
            'Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Gecko/20100101 Firefox/126.0',
            'Firefox 126 auf Linux',
        ];
        // Edge nennt sich auch Chrome, deshalb muss die Reihenfolge stimmen
        yield 'Edge gibt sich als Chrome aus' => [
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            'Edge 120 auf Windows 10/11',
        ];
        yield 'Safari auf dem iPhone' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1',
            'Safari 17 auf iOS',
        ];
        yield 'Opera gibt sich als Chrome aus' => [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/119.0.0.0 Safari/537.36 OPR/105.0.0.0',
            'Opera 105 auf macOS',
        ];
    }

    public function testErkenntSuchmaschinenUndNenntSieBeimNamen(): void
    {
        self::assertTrue(UserAgent::isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        self::assertSame('Googlebot', UserAgent::describe('Mozilla/5.0 (compatible; Googlebot/2.1)'));
    }

    public function testEinBotOhneNamenHeisstSuchmaschine(): void
    {
        self::assertSame('Suchmaschine', UserAgent::describe('irgendein crawler'));
    }

    public function testKennungenOhneBrowserBleibenUnangetastet(): void
    {
        self::assertSame('Unbekannt', UserAgent::describe(''));
        self::assertSame('Unbekannt', UserAgent::describe(null));
        self::assertSame('curl/8.4.0', UserAgent::describe('curl/8.4.0'));
    }

    public function testEineSehrLangeKennungWirdGekuerzt(): void
    {
        $agent = str_repeat('x', 100);
        $result = UserAgent::describe($agent);

        self::assertSame(58, mb_strlen($result));
        self::assertStringEndsWith('…', $result);
    }
}
