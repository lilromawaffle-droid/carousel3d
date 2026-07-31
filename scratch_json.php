<?php
require 'application/config/database.php';
$db = new PDO('mysql:host=localhost;dbname=carousel', 'root', '');

$sql = "SELECT i.*, c.name as category_name, c.slug as category_slug 
        FROM items i JOIN categories c ON i.category_id = c.id ORDER BY c.name, i.id";
$stmt = $db->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($items as $item) {
    $cat_slug = $item['category_slug'];
    if (!isset($data[$cat_slug])) {
        $data[$cat_slug] = ['name' => $item['category_name'], 'items' => []];
    }
    $data[$cat_slug]['items'][] = [
        'id' => $item['id'],
        'bg_color' => $item['bg_color']
    ];
}
echo json_encode($data);
