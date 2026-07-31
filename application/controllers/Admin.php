<?php
// application/controllers/Admin.php

class Admin extends Controller {

    public function index() {
        $item_model = $this->load_model('Item_model');
        $data = [
            'categories' => $item_model->get_categories(),
            'items' => $item_model->get_items(),
            'message' => isset($_GET['msg']) ? $_GET['msg'] : '',
            'status' => isset($_GET['status']) ? $_GET['status'] : ''
        ];
        $this->load_view('admin_dashboard', $data);
    }

    public function delete_category() {
        if (isset($_GET['id'])) {
            $item_model = $this->load_model('Item_model');
            $item_model->delete_category($_GET['id']);
            header("Location: ?c=Admin&status=success&msg=Kategori berhasil dihapus!");
            exit;
        }
    }

    public function delete_item() {
        if (isset($_GET['id'])) {
            $item_model = $this->load_model('Item_model');
            $item_model->delete_item($_GET['id']);
            header("Location: ?c=Admin&status=success&msg=Item berhasil dihapus!");
            exit;
        }
    }

    public function save_category() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '_', $name));
            $slug = preg_replace('/[^A-Za-z0-9\_]/', '', $slug);

            $item_model = $this->load_model('Item_model');
            try {
                if (!empty($_POST['id'])) {
                    // Update
                    $item_model->update_category($_POST['id'], $slug, $name);
                    header("Location: ?c=Admin&status=success&msg=Kategori berhasil diupdate!");
                } else {
                    // Insert
                    $item_model->add_category($slug, $name);
                    header("Location: ?c=Admin&status=success&msg=Kategori berhasil ditambahkan!");
                }
            } catch (Exception $e) {
                header("Location: ?c=Admin&status=error&msg=Gagal memproses kategori: " . $e->getMessage());
            }
            exit;
        }
    }

    public function save_item() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process custom specs
            $spec_keys = $_POST['spec_keys'] ?? [];
            $spec_values = $_POST['spec_values'] ?? [];
            $custom_specs = [];
            for ($i = 0; $i < count($spec_keys); $i++) {
                if (!empty(trim($spec_keys[$i])) && !empty(trim($spec_values[$i]))) {
                    $custom_specs[trim($spec_keys[$i])] = trim($spec_values[$i]);
                }
            }

            $data = [
                'category_id' => $_POST['category_id'],
                'name' => $_POST['name'],
                'tag' => $_POST['tag'],
                'scale' => isset($_POST['scale']) && $_POST['scale'] !== '' ? (float)$_POST['scale'] : 1.0,
                'bg_color' => isset($_POST['auto_color']) ? '' : ($_POST['bg_color'] ?? ''),
                'description' => $_POST['description'],
                'custom_specs' => json_encode($custom_specs)
            ];

            // Handle file path (manual vs upload)
            $path = '';
            if (isset($_FILES['file_3d']) && $_FILES['file_3d']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['file_3d']['tmp_name'];
                $filename = basename($_FILES['file_3d']['name']);
                $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $filename);
                $destination = BASEPATH . '/models/' . $filename;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $path = 'models/' . $filename;
                } else {
                    header("Location: ?c=Admin&status=error&msg=Gagal memindahkan file yang di-upload.");
                    exit;
                }
            } else if (!empty($_POST['manual_path'])) {
                $filename = trim($_POST['manual_path']);
                if (strpos($filename, 'models/') !== 0) {
                    $filename = 'models/' . $filename;
                }
                $path = $filename;
            } else if (!empty($_POST['existing_path'])) {
                // For edit mode without changing file
                $path = $_POST['existing_path'];
            } else {
                // If user doesn't upload a file and doesn't provide a path, use a dummy path
                // so the JS loader fails and shows the default placeholder.
                $path = 'dummy.glb';
            }

            $data['path'] = $path;
            $item_model = $this->load_model('Item_model');
            
            try {
                if (!empty($_POST['id'])) {
                    // Update
                    $item_model->update_item($_POST['id'], $data);
                    header("Location: ?c=Admin&status=success&msg=Item berhasil diupdate!");
                } else {
                    // Insert
                    $item_model->add_item($data);
                    header("Location: ?c=Admin&status=success&msg=Item berhasil ditambahkan!");
                }
            } catch (Exception $e) {
                header("Location: ?c=Admin&status=error&msg=Gagal memproses item: " . $e->getMessage());
            }
            exit;
        }
    }
}
