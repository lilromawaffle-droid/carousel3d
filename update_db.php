<?php
require 'application/config/database.php';
$db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');
$db->exec("UPDATE items SET path='models/Keyboard.glb' WHERE id=1");
$db->exec("UPDATE items SET path='models/MouseApple.fbx' WHERE id=2");
$db->exec("UPDATE items SET path='models/Canon.glb' WHERE id=3");
$db->exec("UPDATE items SET path='models/Cam60D.fbx' WHERE id=4");
$db->exec("UPDATE items SET path='models/Mouse.glb' WHERE id=5");
echo "Updated database paths.";
