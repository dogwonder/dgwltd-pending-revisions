<?php
/**
 * Plugin Activation Handler
 *
 * @package DGW\PendingRevisions\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Core;

/**
 * Plugin Activation Handler
 *
 * Handles tasks that need to be performed when the plugin is activated.
 *
 * @since 1.0.0
 */
class Activator {
    
    /**
     * Plugin activation tasks
     *
     * @since 1.0.0
     * @return void
     */
    public static function activate(): void {
        // Check WordPress and PHP version requirements
        self::check_requirements();
        
        // Add custom capabilities
        self::add_capabilities();
        
        // Create database tables if needed
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule any necessary cron jobs
        self::schedule_cron_jobs();
        
        // Log activation
        error_log('DGW Pending Revisions plugin activated successfully');
    }
    
    /**
     * Check system requirements
     *
     * @since 1.0.0
     * @throws \Exception If requirements are not met
     * @return void
     */
    private static function check_requirements(): void {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '6.0', '<')) {
            throw new \Exception(
                esc_html__('DGW Pending Revisions requires WordPress 6.0 or higher.', 'dgwltd-pending-revisions')
            );
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            throw new \Exception(
                esc_html__('DGW Pending Revisions requires PHP 8.0 or higher.', 'dgwltd-pending-revisions')
            );
        }
        
        // Check for required PHP extensions
        $required_extensions = ['json', 'mbstring'];
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new \Exception(
                    sprintf(
                        esc_html__('DGW Pending Revisions requires the %s PHP extension.', 'dgwltd-pending-revisions'),
                        $extension
                    )
                );
            }
        }
    }
    
    /**
     * Add custom capabilities
     *
     * @since 1.0.0
     * @return void
     */
    private static function add_capabilities(): void {
        // Get administrator role
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Add accept_revisions capability to administrators
            $admin_role->add_cap('accept_revisions');
            $admin_role->add_cap('manage_pending_revisions');
            $admin_role->add_cap('view_revision_analytics');
        }
        
        // Get editor role
        $editor_role = get_role('editor');
        
        if ($editor_role) {
            // Add accept_revisions capability to editors
            $editor_role->add_cap('accept_revisions');
            $editor_role->add_cap('manage_pending_revisions');
        }
    }
    
    /**
     * Create database tables if needed
     *
     * @since 1.0.0
     * @return void
     */
    private static function create_database_tables(): void {
        global $wpdb;
        
        // Get the WordPress charset and collate
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for revision metadata (if needed beyond WordPress meta)
        $table_name = $wpdb->prefix . 'dgw_revision_meta';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            revision_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY revision_id (revision_id),
            KEY post_id (post_id),
            KEY meta_key (meta_key),
            KEY post_meta_key (post_id, meta_key),
            KEY revision_meta_key (revision_id, meta_key),
            KEY created_at_idx (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Add essential indexes to WordPress posts table for revision performance
        self::add_revision_indexes();
        
        // Store database version for future upgrades
        update_option('dgw_pending_revisions_db_version', '1.0.0');
    }
    
    /**
     * Add essential indexes to WordPress tables for revision queries
     *
     * @since 1.0.0
     * @return void
     */
    private static function add_revision_indexes(): void {
        global $wpdb;
        
        // Check if indexes already exist to avoid errors
        $existing_indexes = $wpdb->get_results(
            "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name IN ('post_type_status_modified_idx', 'parent_type_idx')",
            ARRAY_A
        );
        
        $existing_index_names = array_column($existing_indexes, 'Key_name');
        
        // Add index for revision queries (post_type, post_status, post_modified)
        if (!in_array('post_type_status_modified_idx', $existing_index_names, true)) {
            $wpdb->query(
                "ALTER TABLE {$wpdb->posts} ADD INDEX post_type_status_modified_idx (post_type, post_status, post_modified)"
            );
        }
        
        // Add index for parent-child relationships (post_parent, post_type)
        if (!in_array('parent_type_idx', $existing_index_names, true)) {
            $wpdb->query(
                "ALTER TABLE {$wpdb->posts} ADD INDEX parent_type_idx (post_parent, post_type)"
            );
        }
        
        error_log('DGW Pending Revisions: Essential database indexes added');
    }
    
    /**
     * Set default plugin options
     *
     * @since 1.0.0
     * @return void
     */
    private static function set_default_options(): void {
        $default_options = [
            'dgw_pending_revisions_settings' => [
                'post_default_editing_mode' => 'open',
                'page_default_editing_mode' => 'open',
                'auto_cleanup_old_revisions' => false,
                'revision_retention_days' => 30
            ],
        ];
        
        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
        
        // Set plugin version
        update_option('dgw_pending_revisions_version', DGW_PENDING_REVISIONS_VERSION);
    }
    
    /**
     * Schedule cron jobs
     *
     * @since 1.0.0
     * @return void
     */
    private static function schedule_cron_jobs(): void {
        // Schedule daily cleanup job
        if (!wp_next_scheduled('dgw_pending_revisions_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'dgw_pending_revisions_daily_cleanup');
        }
        
        // Schedule weekly analytics update
        if (!wp_next_scheduled('dgw_pending_revisions_weekly_analytics')) {
            wp_schedule_event(time(), 'weekly', 'dgw_pending_revisions_weekly_analytics');
        }
    }
}