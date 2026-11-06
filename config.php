<?php
define('DB_HOST', 'db');
define('DB_USER', 'admin');
define('DB_PASS', 'admin');
define('DB_NAME', 'biblioteca_ohara');

// Obtiene la conexión a la base de datos mediante MySQLi.
function getMySQLiConnection(): mysqli {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die("Error de conexión a la base de datos: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
?>