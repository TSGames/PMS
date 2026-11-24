<?php
/**
 * Decodes a mail address obfuscated by make_mail() (functions_mail.php)
 * and redirects to mailto:.
 *
 * The offset must match make_mail(). Unquoted date(Y) used to throw a
 * fatal error under PHP 8, so every protected mail link ended in a 500.
 */

$time = (date("Y") % 6 - date("m") * 2) * 2;
if ($time < 0) {
    $time *= -1;
}

$address = "";
foreach (explode("_", (string)($_GET["mail"] ?? "")) as $code) {
    $char = (int)$code - $time;
    if ($char < 32 || $char > 126) {
        // Only printable ASCII; anything else is not a link from make_mail()
        http_response_code(400);
        exit;
    }
    $address .= chr($char);
}

if ($address === "") {
    http_response_code(400);
    exit;
}

header("Location: mailto:" . $address);
echo '<script type="text/javascript">
history.back();
</script>';
