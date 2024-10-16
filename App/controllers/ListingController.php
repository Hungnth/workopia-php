<?php


namespace App\Controllers;

use Framework\Database;
use Framework\Validation;
use Framework\Session;
use Framework\Authorization;

class ListingController
{
    protected $db;

    public function __construct()
    {
        $config = require base_path('config/db.php');
        $this->db = new Database($config);
    }

    /**
     * Show all listings
     *
     * @return void
     */
    public function index()
    {
        $listings = $this->db->query('SELECT * FROM listings ORDER BY created_at DESC')->fetchAll();

        load_view('listings/index', [
            'listings' => $listings,
        ]);
    }

    /**
     * Show a create listing form
     *
     * @return void
     */
    public function create()
    {
        load_view('listings/create');
    }

    /**
     * Show a single listing
     *
     * @param $params
     * @return void
     */
    public function show($params)
    {
        $id = $params['id'] ?? '';

        $params = [
            'id' => $id
        ];

        $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

        // Check listing exists
        if (!$listing) {
            ErrorController::not_found('Listing not found');
            return;
        }

        load_view('listings/show', [
            'listing' => $listing
        ]);
    }

    /**
     * Store data in database
     *
     * @return void
     */
    public function store()
    {
        $allowed_fields = ['title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'phone', 'email', 'requirements', 'benefits'];

        $new_listing_data = array_intersect_key($_POST, array_flip($allowed_fields));

        $new_listing_data['user_id'] = Session::get('user')['id'];

        $new_listing_data = array_map('sanitize', $new_listing_data);

        $required_fields = ['title', 'description', 'salary', 'email', 'city', 'state'];

        $errors = [];

        foreach ($required_fields as $field) {
            if (empty($new_listing_data[$field]) || !Validation::string($new_listing_data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        if (!empty($errors)) {
            // Reload view with error
            load_view('listings/create', [
                'errors' => $errors,
                'listing' => $new_listing_data
            ]);
        } else {
            // Submit Data
            $fields = [];

            foreach ($new_listing_data as $field => $value) {
                $fields[] = $field;
            }

            $fields = implode(', ', $fields);

            $values = [];

            foreach ($new_listing_data as $field => $value) {
                // Convert empty strings to null
                if ($value === '') {
                    $new_listing_data[$field] = null;
                }
                $values[] = ':' . $field;
            }
            $values = implode(', ', $values);

            $query = "INSERT INTO listings ({$fields}) VALUES ({$values})";

            $this->db->query($query, $new_listing_data);

            Session::set_flash_msg('success_message', 'Listing created successfully');

            redirect('/listings');

        }
    }

    /**
     * Delete a listing
     *
     * @param array $params
     * @return void
     */
    public function destroy($params)
    {
        $id = $params['id'] ?? '';

        $params = [
            'id' => $id,
        ];

        $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

        // Check if listing exists
        if (!$listing) {
            ErrorController::not_found('Listing not found');
            return;
        }

        // Authorization
        if (!Authorization::is_owner($listing->user_id)) {
            Session::set_flash_msg('error_message', 'You are not authorized to delete this listing!');
            redirect('/listings/' . $listing->id);
        }

        $this->db->query('DELETE FROM listings WHERE id = :id', $params);

        // Set flat message
        Session::set_flash_msg('success_message', 'Listing deleted successfully');

        redirect('/listings');
    }

    /**
     * Show the listing edit form
     *
     * @param $params
     * @return void
     */
    public function edit($params)
    {
        $id = $params['id'] ?? '';

        $params = [
            'id' => $id
        ];

        $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

        // Check listing exists
        if (!$listing) {
            ErrorController::not_found('Listing not found');
            return;
        }

        // Authorization
        if (!Authorization::is_owner($listing->user_id)) {
            Session::set_flash_msg('error_message', 'You are not authorized to update this listing!');
            redirect('/listings/' . $listing->id);
        }

        load_view('listings/edit', [
            'listing' => $listing
        ]);
    }

    /**
     * Update a listing
     *
     * @param array $params
     * @return void
     */
    public function update($params)
    {
        $id = $params['id'] ?? '';

        $params = [
            'id' => $id
        ];

        $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

        // Check listing exists
        if (!$listing) {
            ErrorController::not_found('Listing not found');
            return;
        }

        // Authorization
        if (!Authorization::is_owner($listing->user_id)) {
            Session::set_flash_msg('error_message', 'You are not authorized to update this listing!');
            redirect('/listings/' . $listing->id);
        }

        $allowed_fields = ['title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'phone', 'email', 'requirements', 'benefits'];

        $update_values = [];

        $update_values = array_intersect_key($_POST, array_flip($allowed_fields));

        $update_values = array_map('sanitize', $update_values);

        $required_fields = ['title', 'description', 'salary', 'email', 'city', 'state'];

        $errors = [];

        foreach ($required_fields as $field) {
            if (empty($update_values[$field]) || !Validation::string($update_values[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        if (!empty($errors)) {
            load_view('listings/edit', [
                'listing' => $listing,
                'errors' => $errors,
            ]);
            exit;
        } else {
            // Submit to database
            $update_fields = [];

            foreach (array_keys($update_values) as $field) {
                $update_fields[] = "{$field} = :{$field}";
            }

            $update_fields = implode(', ', $update_fields);

            $update_query = "UPDATE listings SET $update_fields WHERE id = :id";

            $update_values['id'] = $id;
            $this->db->query($update_query, $update_values);

            // Set flat message
            Session::set_flash_msg('success_message', 'Listing updated successfully');

            redirect('/listings/' . $id);

            inspect_and_die($update_query);
        }
    }

    /**
     * Search listings by keywords/location
     *
     * @return void
     */
    public function search()
    {
        $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
        $location = isset($_GET['location']) ? trim($_GET['location']) : '';

        $query = "SELECT * FROM listings WHERE (title LIKE :keywords OR description LIKE :keywords OR tags LIKE :keywords OR company LIKE :keywords) AND (city LIKE :location OR state LIKE :location)";

        $params = [
            'keywords' => "%{$keywords}%",
            'location' => "%{$location}%",
        ];

        $listings = $this->db->query($query, $params)->fetchAll();

        load_view('listings/index', [
            'listings' => $listings,
            'keywords' => $keywords,
            'location' => $location
        ]);
    }
}
