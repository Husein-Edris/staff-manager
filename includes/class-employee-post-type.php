<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Employee Post Type - Simplified
 */
class RT_Employee_Post_Type_V2 {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'add_capabilities'));
        add_filter('manage_angestellte_v2_posts_columns', array($this, 'custom_columns'));
        add_action('manage_angestellte_v2_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('pre_get_posts', array($this, 'filter_posts_for_kunden'));
        add_filter('map_meta_cap', array($this, 'map_employee_meta_caps'), 10, 4);
        add_filter('wp_count_posts', array($this, 'filter_counts_for_kunden'), 10, 3);
    }
    
    /**
     * Register post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Mitarbeiter', 'staff-manager'),
            'singular_name' => __('Mitarbeiter', 'staff-manager'),
            'menu_name' => __('Mitarbeiter', 'staff-manager'),
            'add_new' => __('Neuer Mitarbeiter', 'staff-manager'),
            'add_new_item' => __('Neuen Mitarbeiter hinzufügen', 'staff-manager'),
            'edit_item' => __('Mitarbeiter bearbeiten', 'staff-manager'),
            'new_item' => __('Neuer Mitarbeiter', 'staff-manager'),
            'view_item' => __('Mitarbeiter anzeigen', 'staff-manager'),
            'search_items' => __('Mitarbeiter suchen', 'staff-manager'),
            'not_found' => __('Keine Mitarbeiter gefunden', 'staff-manager'),
            'not_found_in_trash' => __('Keine Mitarbeiter im Papierkorb', 'staff-manager'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 25,
            'capability_type' => array('angestellte_v2', 'angestellte_v2s'),
            'capabilities' => array(
                'edit_post' => 'edit_angestellte_v2',
                'read_post' => 'read_angestellte_v2',
                'delete_post' => 'delete_angestellte_v2',
                'edit_posts' => 'edit_angestellte_v2s',
                'edit_others_posts' => 'edit_others_angestellte_v2s',
                'publish_posts' => 'publish_angestellte_v2s',
                'read_private_posts' => 'read_private_angestellte_v2s',
                'delete_posts' => 'delete_angestellte_v2s',
                'delete_private_posts' => 'delete_private_angestellte_v2s',
                'delete_published_posts' => 'delete_published_angestellte_v2s',
                'delete_others_posts' => 'delete_others_angestellte_v2s',
                'edit_private_posts' => 'edit_private_angestellte_v2s',
                'edit_published_posts' => 'edit_published_angestellte_v2s',
                'create_posts' => 'edit_angestellte_v2s',
            ),
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('angestellte_v2', $args);
    }
    
    /**
     * Add capabilities to roles
     */
    public function add_capabilities() {
        // Define all employee capabilities
        $employee_caps = array(
            'edit_angestellte_v2',
            'read_angestellte_v2',
            'delete_angestellte_v2',
            'edit_angestellte_v2s',
            'edit_others_angestellte_v2s',
            'publish_angestellte_v2s',
            'read_private_angestellte_v2s',
            'delete_angestellte_v2s',
            'delete_private_angestellte_v2s',
            'delete_published_angestellte_v2s',
            'delete_others_angestellte_v2s',
            'edit_private_angestellte_v2s',
            'edit_published_angestellte_v2s',
        );
        
        // Add all capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($employee_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add limited capabilities to kunden_v2 role
        $kunden_v2_role = get_role('kunden_v2');
        if ($kunden_v2_role) {
            $kunden_caps = array(
                'edit_angestellte_v2',
                'read_angestellte_v2',
                'delete_angestellte_v2',
                'edit_angestellte_v2s',
                'publish_angestellte_v2s',
                'delete_angestellte_v2s',
                'delete_published_angestellte_v2s',
                'edit_published_angestellte_v2s',
            );
            
            foreach ($kunden_caps as $cap) {
                $kunden_v2_role->add_cap($cap);
            }
        }
        
        // Add capabilities to original kunden role too (for backward compatibility)
        $kunden_role = get_role('kunden');
        if ($kunden_role) {
            $kunden_caps = array(
                'edit_angestellte_v2',
                'read_angestellte_v2',
                'delete_angestellte_v2',
                'edit_angestellte_v2s',
                'publish_angestellte_v2s',
                'delete_angestellte_v2s',
                'delete_published_angestellte_v2s',
                'edit_published_angestellte_v2s',
            );
            
            foreach ($kunden_caps as $cap) {
                $kunden_role->add_cap($cap);
            }
        }
    }

    /**
     * Custom admin columns
     */
    public function custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Name', 'staff-manager');
        $new_columns['employee_id'] = __('Mitarbeiter-Nr.', 'staff-manager');
        $new_columns['email'] = __('E-Mail', 'staff-manager');
        $new_columns['employment_type'] = __('Art der Beschäftigung', 'staff-manager');
        $new_columns['status'] = __('Status', 'staff-manager');
        $new_columns['employer'] = __('Arbeitgeber', 'staff-manager');
        $new_columns['pdf_actions'] = __('PDF', 'staff-manager');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'employee_id':
                echo esc_html($post_id);
                break;
                
            case 'email':
                $email = get_post_meta($post_id, 'email', true);
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'employment_type':
                $type = get_post_meta($post_id, 'art_des_dienstverhaltnisses', true);
                echo esc_html($type ?: '—');
                break;
                
            case 'status':
                $status = get_post_meta($post_id, 'status', true) ?: 'active';
                $statuses = array(
                    'active' => __('Beschäftigt', 'staff-manager'),
                    'inactive' => __('Beurlaubt', 'staff-manager'),
                    'suspended' => __('Suspendiert', 'staff-manager'),
                    'terminated' => __('Ausgeschieden', 'staff-manager'),
                );
                
                $status_label = isset($statuses[$status]) ? $statuses[$status] : $status;
                $color = $status === 'active' ? 'green' : ($status === 'terminated' ? 'red' : 'orange');
                echo '<span style="color: ' . esc_attr($color) . ';">' . esc_html($status_label) . '</span>';
                break;
                
            case 'employer':
                $employer_id = get_post_meta($post_id, 'employer_id', true);
                if ($employer_id) {
                    $user = get_user_by('id', $employer_id);
                    if ($user) {
                        echo esc_html($user->display_name);
                    } else {
                        echo __('Unbekannt', 'staff-manager');
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'pdf_actions':
                // Generate direct PDF view URL
                $pdf_url = wp_nonce_url(
                    admin_url('admin-ajax.php?action=generate_and_view_employee_pdf&employee_id=' . $post_id),
                    'generate_view_pdf_v2',
                    'nonce'
                );
                
                echo '<a href="' . esc_url($pdf_url) . '" class="button button-small" target="_blank">';
                echo __('PDF Anzeigen', 'staff-manager');
                echo '</a>';
                break;
        }
    }
    
    /**
     * Check if user has a kunde role (kunden or kunden_v2) and is not an admin
     */
    private static function is_kunde_user($user) {
        return (in_array('kunden', $user->roles) || in_array('kunden_v2', $user->roles))
            && !in_array('administrator', $user->roles);
    }

    /**
     * Filter posts for kunden users - only show their own employees
     */
    public function filter_posts_for_kunden($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'angestellte_v2') {
            return;
        }
        
        $user = wp_get_current_user();
        if (self::is_kunde_user($user)) {
            $query->set('meta_key', 'employer_id');
            $query->set('meta_value', $user->ID);
        }
    }

    /**
     * Filter post counts for kunden users to show only their own
     */
    public function filter_counts_for_kunden($counts, $type, $perm) {
        if ($type !== 'angestellte_v2') {
            return $counts;
        }

        $user = wp_get_current_user();
        if (!self::is_kunde_user($user)) {
            return $counts;
        }

        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_status, COUNT(*) AS num_posts FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'employer_id' AND pm.meta_value = %s WHERE p.post_type = %s GROUP BY p.post_status",
            $user->ID,
            'angestellte_v2'
        ), ARRAY_A);

        $new_counts = new stdClass();
        foreach (get_post_stati() as $state) {
            $new_counts->{$state} = 0;
        }
        foreach ($results as $row) {
            $new_counts->{$row['post_status']} = (int) $row['num_posts'];
        }

        return $new_counts;
    }

    /**
     * Map meta capabilities for employee posts
     */
    public function map_employee_meta_caps($caps, $cap, $user_id, $args) {
        if (!isset($args[0]) || get_post_type($args[0]) !== 'angestellte_v2') {
            return $caps;
        }

        $post = get_post($args[0]);
        $user = get_userdata($user_id);

        if (in_array('administrator', $user->roles)) {
            return $caps;
        }

        if (self::is_kunde_user($user)) {
            $employer_id = get_post_meta($post->ID, 'employer_id', true);
            // Allow if employer_id matches, or post has no employer_id yet (new post by this user)
            if ($employer_id == $user_id || (empty($employer_id) && (int) $post->post_author === (int) $user_id)) {
                return array('exist');
            }
            return array('do_not_allow');
        }

        return $caps;
    }
}