<?php
require 'application/config/database.php';
$db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');
$stmt = $db->query('SELECT * FROM items');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
