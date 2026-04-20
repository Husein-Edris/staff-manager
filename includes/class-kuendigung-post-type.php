<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kündigung Post Type - Termination notices
 */
class RT_Kuendigung_Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'add_capabilities'));
    }
    
    /**
     * Register post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Kündigungen', 'staff-manager'),
            'singular_name' => __('Kündigung', 'staff-manager'),
            'menu_name' => __('Kündigungen', 'staff-manager'),
            'add_new' => __('Neue Kündigung', 'staff-manager'),
            'add_new_item' => __('Neue Kündigung erstellen', 'staff-manager'),
            'edit_item' => __('Kündigung bearbeiten', 'staff-manager'),
            'new_item' => __('Neue Kündigung', 'staff-manager'),
            'view_item' => __('Kündigung anzeigen', 'staff-manager'),
            'search_items' => __('Kündigungen suchen', 'staff-manager'),
            'not_found' => __('Keine Kündigungen gefunden', 'staff-manager'),
            'not_found_in_trash' => __('Keine Kündigungen im Papierkorb', 'staff-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // Hidden from main menu, accessed via employee screen
            'capability_type' => array('kuendigung', 'kuendigungs'),
            'capabilities' => array(
                'edit_post' => 'edit_kuendigung',
                'read_post' => 'read_kuendigung',
                'delete_post' => 'delete_kuendigung',
                'edit_posts' => 'edit_kuendigungs',
                'edit_others_posts' => 'edit_others_kuendigungs',
                'publish_posts' => 'publish_kuendigungs',
                'read_private_posts' => 'read_private_kuendigungs',
                'delete_posts' => 'delete_kuendigungs',
                'delete_private_posts' => 'delete_private_kuendigungs',
                'delete_published_posts' => 'delete_published_kuendigungs',
                'delete_others_posts' => 'delete_others_kuendigungs',
                'edit_private_posts' => 'edit_private_kuendigungs',
                'edit_published_posts' => 'edit_published_kuendigungs',
                'create_posts' => 'edit_kuendigungs',
            ),
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('kuendigung', $args);
    }
    
    /**
     * Add capabilities to roles
     */
    public function add_capabilities() {
        $kuendigung_caps = array(
            'edit_kuendigung',
            'read_kuendigung',
            'delete_kuendigung',
            'edit_kuendigungs',
            'edit_others_kuendigungs',
            'publish_kuendigungs',
            'read_private_kuendigungs',
            'delete_kuendigungs',
            'delete_private_kuendigungs',
            'delete_published_kuendigungs',
            'delete_others_kuendigungs',
            'edit_private_kuendigungs',
            'edit_published_kuendigungs',
        );
        
        // Add all capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($kuendigung_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add limited capabilities to kunden role
        $kunden_role = get_role('kunden');
        if ($kunden_role) {
            $kunden_caps = array(
                'edit_kuendigung',
                'read_kuendigung',
                'delete_kuendigung',
                'edit_kuendigungs',
                'publish_kuendigungs',
                'delete_kuendigungs',
                'delete_published_kuendigungs',
                'edit_published_kuendigungs',
            );
            
            foreach ($kunden_caps as $cap) {
                $kunden_role->add_cap($cap);
            }
        }
    }
}
