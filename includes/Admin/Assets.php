<?php
/**
 * Admin Assets Handler
 *
 * @package DGW\PendingRevisions\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Admin;

/**
 * Admin Assets Handler
 *
 * Handles enqueuing of admin scripts and styles.
 *
 * @since 1.0.0
 */
class Assets {
    
    /**
     * Plugin version
     *
     * @since 1.0.0
     * @var string
     */
    private string $version;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $version Plugin version
     */
    public function __construct(string $version) {
        $this->version = $version;
        
        // Hook directly to WordPress actions
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
    }
    
    /**
     * Enqueue admin styles
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook suffix
     * @return void
     */
    public function enqueue_styles(string $hook_suffix): void {
        
        // For dashboard pages, always enqueue (temporary fix)
        if (strpos($hook_suffix, 'dgw-pending-revisions') !== false) {
            $this->enqueue_dashboard_styles($hook_suffix);
            return;
        }
        
        // Enqueue styles only where needed
        $allowed_pages = [
            'post.php',
            'post-new.php',
            'edit.php',
            'revision.php',
            'settings_page_dgw-pending-revisions-settings',
            'toplevel_page_dgw-pending-revisions',
            'pending-revisions_page_dgw-pending-revisions-list',
            'pending-revisions_page_dgw-pending-revisions-analytics',
        ];
        
        if (!in_array($hook_suffix, $allowed_pages, true)) {
            return;
        }
        
        // Main editor stylesheet - consolidated styles for all admin pages
        wp_enqueue_style(
            'dgwltd-pending-revisions-editor',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/sidebar.css',
            ['wp-components'],
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue admin scripts
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook suffix
     * @return void
     */
    public function enqueue_scripts(string $hook_suffix): void {
        
        // Enqueue scripts only where needed
        $allowed_pages = [
            'post.php',
            'post-new.php',
            'edit.php',
            'revision.php',
            'settings_page_dgw-pending-revisions-settings',
            'toplevel_page_dgw-pending-revisions',
            'pending-revisions_page_dgw-pending-revisions-list',
            'pending-revisions_page_dgw-pending-revisions-analytics',
        ];
        
        // For dashboard pages, always enqueue (temporary fix)
        if (strpos($hook_suffix, 'dgw-pending-revisions') !== false) {
            $this->enqueue_dashboard_scripts($hook_suffix);
            return;
        }
        
        if (!in_array($hook_suffix, $allowed_pages, true)) {
            return;
        }
        
        // Post edit specific scripts
        if (in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
            wp_enqueue_script(
                'dgwltd-pending-revisions-post-edit',
                DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/post-edit.js',
                [
                    'wp-components',
                    'wp-element',
                    'wp-data',
                    'wp-core-data',
                    'wp-compose',
                    'wp-api-fetch',
                    'wp-i18n',
                ],
                $this->version,
                true
            );
            
            // Localize script with post data
            global $post;
            if ($post) {
                wp_localize_script(
                    'dgwltd-pending-revisions-post-edit',
                    'dgwPendingRevisionsPostData',
                    [
                        'postId' => $post->ID,
                        'postType' => $post->post_type,
                        'postStatus' => $post->post_status,
                        'editingMode' => $this->get_post_editing_mode($post->ID),
                        'canAcceptRevisions' => current_user_can('accept_revisions', $post->ID),
                        'apiUrl' => rest_url('dgw-pending-revisions/v1/'),
                        'nonce' => wp_create_nonce('wp_rest'),
                    ]
                );
            }
            
            // Meta box quick switch functionality
            $meta_box_asset_file = DGW_PENDING_REVISIONS_PLUGIN_DIR . 'build/meta-box.asset.php';
            $meta_box_asset = file_exists($meta_box_asset_file) ? require $meta_box_asset_file : ['dependencies' => [], 'version' => $this->version];
            
            wp_enqueue_script(
                'dgwltd-pending-revisions-meta-box',
                DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/meta-box.js',
                $meta_box_asset['dependencies'],
                $meta_box_asset['version'],
                true
            );
        }
        
        // Dashboard page scripts - only basic JS needed for dashboard
        if (in_array($hook_suffix, ['toplevel_page_dgw-pending-revisions'], true)) {
            // No additional JS needed for the simple dashboard
        }
        
        // Settings page scripts
        if ($hook_suffix === 'settings_page_dgw-pending-revisions-settings') {
            wp_enqueue_script(
                'dgwltd-pending-revisions-settings',
                DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/settings.js',
                [
                    'wp-components',
                    'wp-element',
                    'wp-i18n',
                ],
                $this->version,
                true
            );
        }
    }
    
    /**
     * Enqueue dashboard-specific scripts
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook suffix
     * @return void
     */
    private function enqueue_dashboard_scripts(string $hook_suffix): void {
        // Dashboard uses simple PHP - no JavaScript needed
    }
    
    /**
     * Enqueue dashboard-specific styles
     *
     * @since 1.0.0
     * @param string $hook_suffix Current admin page hook suffix
     * @return void
     */
    private function enqueue_dashboard_styles(string $hook_suffix): void {
        // Use consolidated editor stylesheet
        wp_enqueue_style(
            'dgwltd-pending-revisions-editor',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/sidebar.css',
            ['wp-components'],
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue Block Editor assets
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_block_editor_assets(): void {
        global $post;
        
        // Only enqueue on supported post types
        if (!$post || !$this->is_supported_post_type($post->post_type)) {
            return;
        }
        
        // Block Editor sidebar plugin
        wp_enqueue_script(
            'dgwltd-pending-revisions-sidebar',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/sidebar.js',
            [
                'wp-blocks',
                'wp-edit-post',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-plugins',
                'wp-compose',
                'wp-i18n',
                'wp-api-fetch',
            ],
            $this->version,
            true
        );
        
        // Block Editor styles (using consolidated sidebar.css)
        wp_enqueue_style(
            'dgwltd-pending-revisions-editor',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/sidebar.css',
            ['wp-edit-blocks'],
            $this->version,
            'all'
        );
        
        // Localize script with editor data
        wp_localize_script(
            'dgwltd-pending-revisions-sidebar',
            'dgwPendingRevisionsEditorData',
            [
                'postId' => $post->ID,
                'postType' => $post->post_type,
                'postStatus' => $post->post_status,
                'editingMode' => $this->get_post_editing_mode($post->ID),
                'acceptedRevisionId' => get_post_meta($post->ID, '_dpr_accepted_revision_id', true),
                'canAcceptRevisions' => current_user_can('accept_revisions', $post->ID),
                'apiUrl' => rest_url('dgw-pending-revisions/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => get_current_user_id(),
            ]
        );
        
        // Set script translations
        wp_set_script_translations(
            'dgwltd-pending-revisions-sidebar',
            'dgwltd-pending-revisions',
            DGW_PENDING_REVISIONS_PLUGIN_DIR . 'languages'
        );
    }
    
    /**
     * Check if post type is supported
     *
     * @since 1.0.0
     * @param string $post_type Post type to check
     * @return bool
     */
    private function is_supported_post_type(string $post_type): bool {
        $settings = get_option('dgw_pending_revisions_settings', []);
        $setting_key = $post_type . '_default_editing_mode';
        $editing_mode = $settings[$setting_key] ?? 'off';
        
        return $editing_mode !== 'off';
    }
    
    /**
     * Get post editing mode
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return string
     */
    private function get_post_editing_mode(int $post_id): string {
        $post_type = get_post_type($post_id);
        $settings = get_option('dgw_pending_revisions_settings', []);
        $setting_key = $post_type . '_default_editing_mode';
        $default_mode = $settings[$setting_key] ?? 'open';
        
        // Check for post-specific override
        $post_mode = get_post_meta($post_id, '_dgw_editing_mode', true);
        
        return $post_mode ?: $default_mode;
    }
}