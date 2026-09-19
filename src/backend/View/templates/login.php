<?php
/**
 * Anmeldemaske des Backends.
 *
 * @var string $siteName
 * @var string $rememberedName Benutzername aus dem Cookie
 * @var string $messages       Fertig gerendertes HTML der Meldungen
 */

use Pms\Support\Html;
use Pms\Backend\View\Layout;

echo Layout::head(Layout::title());
?>
<body class="login-page">
<div class="login-wrapper">
<?= $messages ?>
<?= Html::formOpen() ?>
    <div class="login-box">
        <h1 class="login-title">PMS Back End Login</h1>
        <table class="login-form">
            <tr><td><label for="login_name">Benutzername:</label></td>
                <td><input type="text" id="login_name" name="login_name" value="<?= Html::e($rememberedName) ?>" autocomplete="username" autofocus></td></tr>
            <tr><td><label for="login_password">Passwort:</label></td>
                <td><input type="password" id="login_password" name="login_password" autocomplete="current-password"></td></tr>
            <tr><td colspan="2"><label class="login-remember"><input type="checkbox" name="save_login" value="1"> Zugangsdaten auf diesem Computer speichern</label></td></tr>
            <tr><td colspan="2"><div class="login-actions"><input type="submit" name="login" value="Einloggen"></div></td></tr>
        </table>
    </div>
<?= Html::formClose() ?>
</div>
</body>
</html>
