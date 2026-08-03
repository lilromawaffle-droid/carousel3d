<?php
require 'application/config/database.php';
$db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');
try {
    $db->exec('ALTER TABLE items ADD COLUMN bg_color VARCHAR(20) DEFAULT NULL AFTER scale');
    echo 'Column added.';
} catch(Exception $e) {
    echo $e->getMessage();
}
