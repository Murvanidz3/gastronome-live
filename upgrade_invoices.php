<?php
require __DIR__ . '/config/database.php';

try {
    $db = getDB();

    // Add columns if they don't exist
    $columns = [
        'invoice_date' => 'DATE NULL',
        'due_date' => 'DATE NULL',
        'subtotal' => 'DECIMAL(10,2) NULL',
        'tax_rate' => 'DECIMAL(5,2) NULL',
        'tax_amount' => 'DECIMAL(10,2) NULL',
        'discount_amount' => 'DECIMAL(10,2) NULL',
        'items_json' => 'LONGTEXT NULL',
        'notes' => 'TEXT NULL'
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->exec("ALTER TABLE invoices ADD COLUMN $col $def");
            echo "Added $col<br>";
        } catch (PDOException $e) {
            // Likely already exists
            echo "Skipped $col (might already exist)<br>";
        }
    }

    echo "Done upgrading invoices table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
