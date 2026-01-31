<?php
require_once __DIR__ . '/../../config/ls/db.php';

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

function db_conn() {
    try {
        $server_info = 'mysql:dbname=' . DB_NAME . ';charset=utf8;host=' . DB_HOST;
        $pdo = new PDO($server_info, DB_USER, DB_PASS);

        // switch to exception mode
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo; //important
    } catch (PDOException $e) {
        exit('Error: DB connection:' . $e->getMessage());
    }
}

function sql_error($stmt) {
    $error = $stmt->errorInfo();
    exit("SQLError:" . $error[2]);
}

function redirect($path) {
    header("Location: " . $path);
    exit;
}

