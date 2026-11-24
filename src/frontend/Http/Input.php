<?php

namespace Pms\Frontend\Http;

/**
 * Neutralizes the content markers [php] and [code] in visitor input.
 *
 * index.php runs the whole content area through make_dynamic(), which
 * executes [php]...[/php] via eval(). Visitor input such as a search term
 * ends up in that area, so a request like
 * index.php?search_query=[php]...[/php] executed arbitrary code without
 * any login (BEFUNDE B25).
 *
 * Every "[" in request values is replaced by its character reference
 * "&#91;". The browser still shows "[", but no marker can be formed - not
 * even by concatenating several inputs. Editors (user type > 1) keep raw
 * input: they write [php] blocks on purpose when editing content inline.
 */
final class Input
{
    /** Fields that are never displayed and must reach the hash unchanged. */
    private const RAW_KEY_PATTERN = '/pass|_pw$/i';

    public static function text(string $value): string
    {
        return str_replace('[', '&#91;', $value);
    }

    /** Applies text() to $_GET, $_POST, $_COOKIE and $_REQUEST. */
    public static function neutralizeRequest(): void
    {
        $_GET = self::apply($_GET);
        $_POST = self::apply($_POST);
        $_COOKIE = self::apply($_COOKIE);
        $_REQUEST = self::apply($_REQUEST);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<array-key, mixed>
     */
    private static function apply(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $safeKey = is_string($key) ? self::text($key) : $key;
            if (is_array($value)) {
                $result[$safeKey] = self::apply($value);
            } elseif (is_string($value) && !(is_string($key) && preg_match(self::RAW_KEY_PATTERN, $key))) {
                $result[$safeKey] = self::text($value);
            } else {
                $result[$safeKey] = $value;
            }
        }
        return $result;
    }
}
