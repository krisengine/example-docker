<?php

print "<p style='color:green'>" . php_sapi_name() . "</p>";
try {
    $dbh = new PDO(getenv('DB_DSN'), getenv('DB_USER'), getenv('DB_PASSWORD'));
    print "<p style='color:green'>Успешное подключение к базе данных</p>";
} catch (PDOException $e) {
    print "<p style='color:red'>'Подключение не удалось: " . $e->getMessage() . "</p>";
}
