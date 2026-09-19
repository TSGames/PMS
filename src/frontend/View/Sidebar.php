<?php

namespace Pms\Frontend\View;

use Pms\Frontend\Http\Routes;
use Pms\Support\Html;

/**
 * Die Bausteine der Nebenspalte: Anmeldung, Suche, Zaehler.
 *
 * Alle drei waren Layouttabellen mit <center> und <br>, und keiner hatte
 * eine Ueberschrift - ein Stylesheet musste sie sich mit ::before selbst
 * erfinden. Hier tragen sie eine, und die Anordnung entscheidet das
 * Stylesheet statt des Markups.
 */
final class Sidebar
{
    /** Ein Block der Nebenspalte, mit Ueberschrift. */
    public static function block(string $class, string $heading, string $body): string
    {
        if (trim($body) === '') {
            return '';
        }

        return '<section class="sidebar_block ' . Html::e($class) . '">'
            . '<h2 class="sidebar_heading">' . Html::e($heading) . '</h2>'
            . $body
            . '</section>';
    }

    /** Das Suchfeld. */
    public static function search(string $query = ''): string
    {
        $body = form('', 'get')
            . '<div class="search_row">'
            . '<label class="visually_hidden" for="search_query">' . Html::e(language('SEARCH_BUTTON')) . '</label>'
            . '<input type="text" class="search_field" id="search_query" name="search_query" value="' . Html::e($query) . '">'
            . '<input type="submit" class="search_button" name="search" value="' . Html::e(language('SEARCH_BUTTON')) . '">'
            . '</div></form>';

        return self::block('search_plugin', language('SEARCH_BUTTON'), $body);
    }

    /**
     * Die Besucherzahlen.
     *
     * @param array<string, int|string> $values Beschriftung => Wert
     */
    public static function counter(array $values): string
    {
        $rows = '';
        foreach ($values as $label => $value) {
            $rows .= '<div class="user_counter_row">'
                . '<span class="user_counter_label">' . Html::e($label) . '</span> '
                . '<span class="user_counter_value">' . Html::e((string)$value) . '</span>'
                . '</div>';
        }

        return self::block('user_counter', language('COUNTER_HEADING'), $rows);
    }

    /**
     * Die Anmeldemaske.
     *
     * @param string $rememberedName Name aus dem Cookie der letzten Anmeldung
     */
    public static function loginForm(
        string $rememberedName = '',
        string $error = '',
        string $lastLogin = '',
        bool $registerAllowed = false,
        bool $recoverAllowed = false
    ): string {
        $body = '';
        if ($lastLogin !== '') {
            $body .= '<p class="last_login">' . $lastLogin . '</p>';
        }
        if ($error !== '') {
            $body .= '<div class="login_fail">' . $error . '</div>';
        }

        $body .= form() . hidden_positions()
            . '<div class="field"><label for="login_user">' . Html::e(language('USER_NAME')) . '</label>'
            . '<input type="text" id="login_user" name="name" autocomplete="username" value="' . Html::e($rememberedName) . '"></div>'
            . '<div class="field"><label for="login_pass">' . Html::e(language('USER_PW')) . '</label>'
            . '<input type="password" id="login_pass" name="password" autocomplete="current-password"></div>'
            . '<div class="field field_check"><input type="checkbox" id="save_login" name="save_login" value="1">'
            . '<label for="save_login">' . Html::e(language('USER_STAY_LOGGED_IN')) . '</label></div>'
            . '<div class="field field_submit"><input type="submit" name="user_login" value="'
            . Html::e(language('USER_LOGIN')) . '"></div>'
            . '</form>';

        $links = '';
        if ($registerAllowed) {
            $links .= '<li><a class="user_register" href="' . Html::e(Routes::action('register')) . '">'
                . Html::e(language('USER_REGISTER')) . '</a></li>';
        }
        if ($recoverAllowed) {
            $links .= '<li><a class="user_pw_recover" href="' . Html::e(Routes::action('password_recover')) . '">'
                . Html::e(language('USER_PASSWORD_LOST')) . '</a></li>';
        }
        if ($links !== '') {
            $body .= '<ul class="user_links">' . $links . '</ul>';
        }

        return self::block('user_panel', language('USER_LOGIN'), $body);
    }

    /**
     * Der angemeldete Zustand: wer angemeldet ist und was er tun kann.
     *
     * @param string $image Fertiges Bild des Benutzers, oder leer
     */
    public static function userPanel(
        string $name,
        string $lastVisit,
        string $image,
        string $logoutHref,
        string $settingsHref
    ): string {
        $body = '<p class="user_online">' . str_replace('%1', Html::e($name), language('USER_ONLINE')) . '</p>'
            . '<p class="user_last_visit">' . $lastVisit . '</p>';

        if ($image !== '') {
            $body .= '<div class="user_image">' . $image . '</div>';
        }

        $body .= '<ul class="user_links">'
            . '<li><a class="user_settings" href="' . Html::e($settingsHref) . '">'
            . Html::e(language('USER_SETTINGS')) . '</a></li>'
            . '<li><a class="user_logout" href="' . Html::e($logoutHref) . '">'
            . Html::e(language('USER_LOGOUT')) . '</a></li>'
            . '</ul>';

        return self::block('user_panel', Html::e($name), $body);
    }
}
