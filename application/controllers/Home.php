<?php
// application/controllers/Home.php

class Home extends Controller {

    public function index() {
        // Load the model
        $item_model = $this->load_model('Item_model');

        // Fetch data from DB
        $categories = $item_model->get_all_categories_with_items();

        // Pass data to view
        $data = [
            'categories_json' => json_encode($categories)
        ];

        // Load the view
        $this->load_view('carousel_view', $data);
    }
}
