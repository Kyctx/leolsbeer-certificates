<?php
// Скопируйте в config.php рядом с api.php и впишите свои данные.
// config.php не попадает в репозиторий (см. .gitignore).
return [
    'db_host'  => 'localhost',
    'db_name'  => 'u0000000_certs',
    'db_user'  => 'u0000000_certs',
    'db_pass'  => '',
    // Пароль, который продавцы вводят на странице генератора
    'key'      => 'придумайте-длинный-пароль',
    // Адрес страницы на GitHub Pages, без слэша в конце
    'origin'   => 'https://kyctx.github.io',
];
