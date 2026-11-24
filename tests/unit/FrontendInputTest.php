<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Frontend\Http\Input;
use PHPUnit\Framework\TestCase;

/**
 * Frontend\Http\Input - visitor input must not form [php] markers (B25).
 */
final class FrontendInputTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];
    }

    public function testNoMarkerSurvives(): void
    {
        $text = Input::text("[php]echo 1;[/php] [code]x[/code]");
        self::assertStringNotContainsString('[', $text);
        self::assertSame('&#91;php]echo 1;&#91;/php]', Input::text('[php]echo 1;[/php]'));
    }

    public function testAllSuperglobalsAreCoveredIncludingNestedValues(): void
    {
        $_GET = ['search_query' => '[php]', 'list' => ['a' => '[php]']];
        $_POST = ['comment' => '[php]'];
        $_COOKIE = ['note' => '[php]'];
        $_REQUEST = ['search_query' => '[php]'];

        Input::neutralizeRequest();

        self::assertSame('&#91;php]', $_GET['search_query']);
        self::assertSame('&#91;php]', $_GET['list']['a']);
        self::assertSame('&#91;php]', $_POST['comment']);
        self::assertSame('&#91;php]', $_COOKIE['note']);
        self::assertSame('&#91;php]', $_REQUEST['search_query']);
    }

    public function testPasswordsReachTheHashUnchanged(): void
    {
        $_POST = ['password' => 'a[b', 'passwordr' => 'a[b', 'login_password' => 'a[b'];
        $_COOKIE = ['login_pw' => 'x[y'];

        Input::neutralizeRequest();

        self::assertSame('a[b', $_POST['password']);
        self::assertSame('a[b', $_POST['passwordr']);
        self::assertSame('a[b', $_POST['login_password']);
        self::assertSame('x[y', $_COOKIE['login_pw']);
    }
}
