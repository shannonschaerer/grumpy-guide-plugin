<?php

namespace Grumpy\Guide;

use function add_meta_box;
use function wp_nonce_field;

class ParkPostType
{
    protected $postType = 'park';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('add_meta_boxes', [$this, 'addParkMetaBoxes']);
        add_action('save_post', [$this, 'saveParkMetaBoxes']);

        // Admin Column Hooks
        add_filter("manage_{$this->postType}_posts_columns", [$this, 'setColumns']);
        add_action("manage_{$this->postType}_posts_custom_column", [$this, 'renderColumns'], 10, 2);
    }

    public function registerPostType(): void
    {
        register_post_type($this->postType, [
            'labels' => [
                'name' => 'Parks',
                'singular_name' => 'Park',
                 'add_new_item' => 'Add New Park',
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true, // Enables Gutenberg and Block Bindings
            'menu_icon' => 'dashicons-palmtree',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'rewrite' => ['slug' => 'parks'],
        ]);
    }

    public function registerTaxonomies(): void
    {
        register_taxonomy('grumpiness', $this->postType, [
            'label' => 'Grump Factor',
            'rewrite' => ['slug' => 'grump-factor'],
            'hierarchical' => true, // Acts like categories
            'show_in_rest' => true,
        ]);
    }

    public function addParkMetaBoxes()
    {
        add_meta_box(
            'park_details',           // Unique ID
            'Grumpy Metrics',         // Box Title
            [$this, 'renderMetaBox'], // Callback function
            $this->postType,          // Post type
            'side',                   // Context (side or normal)
            'default'                 // Priority
        );
    }

    public function renderMetaBox($post)
    {
        // Add the nonce field for security
        wp_nonce_field('save_park_meta_data', 'park_meta_nonce');   
        $value = get_post_meta($post->ID, 'goose_aggression', true);
        
        echo '<label for="goose_aggression">Goose Aggression (0-5): </label>';
        echo '<input type="number" id="goose_aggression" name="goose_aggression" value="' . esc_attr($value) . '" min="0" max="5" style="width:100%">';
    }

    public function saveParkMetaBoxes($postId)
    {
        // 1. Verify the nonce exists and is valid
        if (!isset($_POST['park_meta_nonce']) || !wp_verify_nonce($_POST['park_meta_nonce'], 'save_park_meta_data')) {
            return;
        }

        // 2. Check if this is an autosave (we don't want to save during autosaves)
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 3. Check user permissions
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // 4. Verify the post type
        if (get_post_type($postId) !== $this->postType) {
            return;
        }

        if (isset($_POST['goose_aggression'])) {
            $aggression = (int) $_POST['goose_aggression'];
            // Clamp the value between 0 and 5
            $aggression = max(0, min(5, $aggression));
            update_post_meta(
                $postId, 
                'goose_aggression', 
                sanitize_text_field($_POST['goose_aggression'])
            );
        }
    }

    public function setColumns($columns)
    {
        $columns['goose_aggression'] = 'Goose Aggression';
        return $columns;
    }

    public function renderColumns($column, $postId)
    {
        if ($column === 'goose_aggression') {
            echo get_post_meta($postId, 'goose_aggression', true) ?: '0';
        }
    }
}