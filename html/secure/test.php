<?php

$host = 'ldap://ldap.pnhz.kz';  // или просто IP
$login = 'g.chirikov';
$password = 'Super@551249';
$dn = 'DC=pnhz,DC=kz';

// === Подключение
$conn = ldap_connect($host);
ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

// === Пробуем сначала bind по UPN (логин@домен)
$domain = strtoupper(str_replace(['DC=', ','], ['.', ''], $dn));
$upn = "$login@$domain";

echo "Пробуем UPN bind с $upn...\n";
$bind = @ldap_bind($conn, $upn, $password);

if ($bind) {
    echo "✅ Успех: bind с $upn прошёл.\n";
} else {
    echo "❌ Не удалось выполнить bind с $upn\n";
    echo "Ошибка: " . ldap_error($conn) . " (errno " . ldap_errno($conn) . ")\n";

    // === Пробуем найти DN и bind по нему
    echo "\nПробуем найти DN через ldap_search...\n";
    $filter = "(samaccountname=$login)";
    $search = ldap_search($conn, $dn, $filter);

    if (!$search) {
        echo "❌ Поиск не удался\n";
    } else {
        $entries = ldap_get_entries($conn, $search);
        if (!empty($entries[0]['dn'])) {
            $userDn = $entries[0]['dn'];
            echo "Найден DN: $userDn\n";
            echo "Пробуем bind с DN...\n";
            $bind = @ldap_bind($conn, $userDn, $password);
            if ($bind) {
                echo "✅ Успех: bind с DN прошёл.\n";
            } else {
                echo "❌ Не удалось выполнить bind с DN\n";
                echo "Ошибка: " . ldap_error($conn) . " (errno " . ldap_errno($conn) . ")\n";
            }
        } else {
            echo "❌ DN не найден в результате поиска.\n";
        }
    }
}

ldap_close($conn);
