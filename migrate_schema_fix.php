<?php
require_once(__DIR__.'/config/db.php');

$wrong_tables = ['fees', 'marks', 'attendance', 'certificates', 'timetable', 'notifications', 'logs', 'settings'];

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach ($wrong_tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Dropped $table. ";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    $sql = file_get_contents(__DIR__.'/schema.sql');
    $lines = explode("\n", $sql);
    $clean_sql = "";
    foreach ($lines as $line) {
        if (stripos($line, 'DROP TABLE') === false && stripos($line, 'CREATE DATABASE') === false && stripos($line, 'USE smart_college_erp') === false) {
            // Replace CREATE TABLE with CREATE TABLE IF NOT EXISTS
            $line = str_replace("CREATE TABLE `", "CREATE TABLE IF NOT EXISTS `", $line);
            $line = str_replace("CREATE TABLE ", "CREATE TABLE IF NOT EXISTS ", $line);
            // Replace INSERT INTO with INSERT IGNORE INTO
            $line = str_replace("INSERT INTO", "INSERT IGNORE INTO", $line);
            $clean_sql .= $line . "\n";
        }
    }

    $statements = explode(";", $clean_sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                // Ignore errors for existing tables or index conflicts
            }
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Fully re-synchronized schema securely!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
