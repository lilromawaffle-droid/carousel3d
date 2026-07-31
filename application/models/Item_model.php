<?php
// application/models/Item_model.php

class Item_model extends Model {

    public function get_all_categories_with_items() {
        $stmt_cat = $this->db->query("SELECT * FROM categories ORDER BY id ASC");
        $categories = $stmt_cat->fetchAll();

        $stmt_item = $this->db->query("SELECT * FROM items ORDER BY id ASC");
        $items = $stmt_item->fetchAll();

        $data = [];
        foreach ($categories as $cat) {
            $cat_slug = $cat['slug'];
            $data[$cat_slug] = [
                'name' => $cat['name'],
                'items' => []
            ];
        }

        foreach ($items as $item) {
            $cat_slug = null;
            foreach ($categories as $cat) {
                if ($cat['id'] == $item['category_id']) {
                    $cat_slug = $cat['slug'];
                    break;
                }
            }

            if ($cat_slug) {
                $position = [
                    (float)($item['position_x'] ?? 0), 
                    (float)($item['position_y'] ?? 0), 
                    (float)($item['position_z'] ?? 0)
                ];
                
                // Decode custom specs or use empty object
                $custom_specs = !empty($item['custom_specs']) ? json_decode($item['custom_specs'], true) : [];

                $data[$cat_slug]['items'][] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'tag' => $item['tag'],
                    'path' => $item['path'],
                    'scale' => (float)$item['scale'],
                    'position' => $position,
                    'desc' => $item['description'],
                    'custom_specs' => $custom_specs
                ];
            }
        }
        return $data;
    }

    public function get_categories() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function add_category($slug, $name) {
        $stmt = $this->db->prepare("INSERT INTO categories (slug, name) VALUES (:slug, :name)");
        $stmt->execute([':slug' => $slug, ':name' => $name]);
        return $this->db->lastInsertId();
    }
    
    public function update_category($id, $slug, $name) {
        $stmt = $this->db->prepare("UPDATE categories SET slug = :slug, name = :name WHERE id = :id");
        return $stmt->execute([':slug' => $slug, ':name' => $name, ':id' => $id]);
    }

    public function add_item($data) {
        $sql = "INSERT INTO items (category_id, name, tag, path, scale, description, custom_specs) 
                VALUES (:category_id, :name, :tag, :path, :scale, :description, :custom_specs)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':tag' => $data['tag'],
            ':path' => $data['path'],
            ':scale' => $data['scale'],
            ':description' => $data['description'],
            ':custom_specs' => $data['custom_specs']
        ]);
        return $this->db->lastInsertId();
    }
    
    public function update_item($id, $data) {
        $sql = "UPDATE items SET 
                category_id = :category_id, 
                name = :name, 
                tag = :tag, 
                path = :path, 
                scale = :scale, 
                description = :description, 
                custom_specs = :custom_specs 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':tag' => $data['tag'],
            ':path' => $data['path'],
            ':scale' => $data['scale'],
            ':description' => $data['description'],
            ':custom_specs' => $data['custom_specs'],
            ':id' => $id
        ]);
        return true;
    }

    public function get_items() {
        $stmt = $this->db->query("SELECT items.*, categories.name as category_name FROM items LEFT JOIN categories ON items.category_id = categories.id ORDER BY items.id DESC");
        return $stmt->fetchAll();
    }

    public function delete_category($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function delete_item($id) {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
