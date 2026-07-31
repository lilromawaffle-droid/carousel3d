<?php
// application/core/Controller.php

class Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Helper function to load view
    protected function load_view($view_name, $data = []) {
        // Extract data to variables
        extract($data);
        
        $view_file = APPPATH . '/views/' . $view_name . '.php';
        if (file_exists($view_file)) {
            require $view_file;
        } else {
            die("View $view_name not found.");
        }
    }

    // Helper function to load model
    protected function load_model($model_name) {
        $model_file = APPPATH . '/models/' . $model_name . '.php';
        if (file_exists($model_file)) {
            require_once $model_file;
            return new $model_name($this->db);
        } else {
            die("Model $model_name not found.");
        }
    }
}
