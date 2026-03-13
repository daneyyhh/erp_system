<?php
require_once(__DIR__ . '/config/db.php');

echo "Starting Migration to Aiven Cloud...\n";

try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split SQL into individual statements
    // This is a simple split, more robust ones exist but for this schema it should work
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $index => $stmt) {
        if (empty($stmt)) continue;
        
        // Skip database creation if it's 'smart_college_erp' since Aiven usually gives we 'defaultdb'
        if (stripos($stmt, 'CREATE DATABASE') !== false) {
            echo "Skipping database creation statement...\n";
            continue;
        }
        if (stripos($stmt, 'USE ') !== false) {
            echo "Skipping USE statement...\n";
            continue;
        }

        $pdo->exec($stmt);
        echo "Executed statement " . ($index + 1) . "\n";
    }

    echo "\n✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "Your Aiven Cloud Database is now populated with the base schema.\n";

} catch (Exception $e) {
    echo "\n❌ MIGRATION FAILED:\n";
    echo $e->getMessage() . "\n";
}
?>
