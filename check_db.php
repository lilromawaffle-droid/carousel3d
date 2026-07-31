<?php
require 'application/config/database.php';
$db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');
$stmt = $db->query('SELECT COUNT(*) FROM categories');
echo 'Categories: ' . $stmt->fetchColumn() . "\n";
$stmt2 = $db->query('SELECT COUNT(*) FROM items');
echo 'Items: ' . $stmt2->fetchColumn() . "\n";
