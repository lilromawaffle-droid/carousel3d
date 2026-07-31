<?php
require 'application/config/database.php';
try {
    $db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("ALTER TABLE items ADD COLUMN custom_specs TEXT");
    echo "Kolom custom_specs berhasil ditambahkan.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
