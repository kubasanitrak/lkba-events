<?php
/**
 * CPT and taxonomies (Phase 0 stubs).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Post_Types {

    const POST_TYPE_EVENT = 'lkba_event';
    const TAX_CATEGORY    = 'lkba_event_cat';
    const TAX_PLACE       = 'lkba_place';

    public function __construct() {
        add_action('init', array($this, 'register_post_types'), 5);
        add_action('init', array($this, 'register_taxonomies'), 6);
    }

    public function register_post_types() {
        $labels = array(
            'name'          => __('Události', 'lkba-events'),
            'singular_name' => __('Událost', 'lkba-events'),
            'all_items'     => __('Všechny události', 'lkba-events'),
            'add_new'       => __('Přidat', 'lkba-events'),
            'add_new_item'  => __('Přidat událost', 'lkba-events'),
            'edit_item'     => __('Upravit událost', 'lkba-events'),
            'menu_name'     => __('Události', 'lkba-events'),
        );

        register_post_type(self::POST_TYPE_EVENT, array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            // Nested under LKBA_Events_Admin::MENU_SLUG — do not also add_submenu_page for edit.php.
            'show_in_menu'        => 'lkba-events',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'udalost',
                'with_front' => false,
            ),
            'has_archive'         => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        ));
    }

    public function register_taxonomies() {
        register_taxonomy(self::TAX_CATEGORY, self::POST_TYPE_EVENT, array(
            'labels' => array(
                'name'          => __('Kategorie událostí', 'lkba-events'),
                'singular_name' => __('Kategorie', 'lkba-events'),
                'menu_name'     => __('Kategorie', 'lkba-events'),
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'kategorie-udalosti'),
        ));

        register_taxonomy(self::TAX_PLACE, self::POST_TYPE_EVENT, array(
            'labels' => array(
                'name'          => __('Místa', 'lkba-events'),
                'singular_name' => __('Místo', 'lkba-events'),
                'menu_name'     => __('Místa', 'lkba-events'),
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'misto-udalosti'),
        ));
    }
}
