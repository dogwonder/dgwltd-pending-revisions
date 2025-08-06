<?php
/**
 * Admin Functionality Handler
 *
 * @package DGW\PendingRevisions\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Admin;

/**
 * Admin Functionality Handler
 *
 * Handles all admin-specific functionality for the plugin.
 *
 * @since 1.0.0
 */
class Admin {
    
    /**
     * Plugin version
     *
     * @since 1.0.0
     * @var string
     */
    private string $version;
    
    /**
     * Plugin text domain
     *
     * @since 1.0.0
     * @var string
     */
    private string $text_domain;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $version Plugin version
     * @param string $text_domain Plugin text domain
     */
    public function __construct(string $version, string $text_domain) {
        $this->version = $version;
        $this->text_domain = $text_domain;
    }
    
    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        add_filter('post_row_actions', [$this, 'add_post_row_actions'], 10, 2);
        add_filter('page_row_actions', [$this, 'add_post_row_actions'], 10, 2);
    }
    
    /**
     * Admin initialization
     *
     * @since 1.0.0
     * @return void
     */
    public function admin_init(): void {
        // Register settings
        register_setting(
            'dgw_pending_revisions_settings',
            'dgw_pending_revisions_settings',
            [$this, 'sanitize_settings']
        );
        
        // Add settings sections and fields
        $this->add_settings_sections();
    }
    
    /**
     * Add settings sections
     *
     * @since 1.0.0
     * @return void
     */
    private function add_settings_sections(): void {
        // General settings section
        add_settings_section(
            'dgw_pending_revisions_general',
            __('General Settings', $this->text_domain),
            [$this, 'general_settings_callback'],
            'dgw_pending_revisions_settings'
        );
        
        // Post types settings
        add_settings_field(
            'post_types_settings',
            __('Post Types', $this->text_domain),
            [$this, 'post_types_settings_callback'],
            'dgw_pending_revisions_settings',
            'dgw_pending_revisions_general'
        );
        
    }
    
    /**
     * General settings section callback
     *
     * @since 1.0.0
     * @return void
     */
    public function general_settings_callback(): void {
        echo '<p>' . esc_html__('Configure the general settings for pending revisions.', $this->text_domain) . '</p>';
    }
    
    /**
     * Post types settings callback
     *
     * @since 1.0.0
     * @return void
     */
    public function post_types_settings_callback(): void {
        $settings = get_option('dgw_pending_revisions_settings', []);
        $post_types = get_post_types(['public' => true], 'objects');
        
        echo '<table class="form-table">';
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }
            
            $setting_key = $post_type->name . '_default_editing_mode';
            $current_value = $settings[$setting_key] ?? 'open';
            
            echo '<tr>';
            echo '<th scope="row">' . esc_html($post_type->label) . '</th>';
            echo '<td>';
            echo '<select name="dgw_pending_revisions_settings[' . esc_attr($setting_key) . ']">';
            
            $modes = [
                'off' => __('Disabled', $this->text_domain),
                'open' => __('Open', $this->text_domain),
                'pending' => __('Requires Approval', $this->text_domain),
            ];
            
            foreach ($modes as $mode_key => $mode_label) {
                echo '<option value="' . esc_attr($mode_key) . '"' . selected($current_value, $mode_key, false) . '>';
                echo esc_html($mode_label);
                echo '</option>';
            }
            
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    /**
     * Sanitize settings
     *
     * @since 1.0.0
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings(array $input): array {
        $sanitized = [];
        
        // Sanitize post type settings
        $post_types = get_post_types(['public' => true]);
        foreach ($post_types as $post_type) {
            if ($post_type === 'attachment') {
                continue;
            }
            
            $setting_key = $post_type . '_default_editing_mode';
            if (isset($input[$setting_key])) {
                $allowed_modes = ['off', 'open', 'pending'];
                $sanitized[$setting_key] = in_array($input[$setting_key], $allowed_modes) ? $input[$setting_key] : 'open';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Add admin menu
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('Pending Revisions Settings', $this->text_domain),
            __('Pending Revisions', $this->text_domain),
            'manage_options',
            'dgw-pending-revisions-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Render settings page
     *
     * @since 1.0.0
     * @return void
     */
    public function settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pending Revisions Settings', $this->text_domain); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dgw_pending_revisions_settings');
                do_settings_sections('dgw_pending_revisions_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Show admin notices
     *
     * @since 1.0.0
     * @return void
     */
    public function show_admin_notices(): void {
        // Check for pending revisions that need attention
        $pending_count = $this->get_pending_revisions_count();
        
        if ($pending_count > 0 && current_user_can('accept_revisions')) {
            printf(
                '<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
                sprintf(
                    esc_html(_n(
                        'There is %d revision pending approval.',
                        'There are %d revisions pending approval.',
                        $pending_count,
                        $this->text_domain
                    )),
                    $pending_count
                ),
                admin_url('edit.php?post_status=pending-revision'),
                esc_html__('Review now', $this->text_domain)
            );
        }
    }
    
    /**
     * Add post row actions
     *
     * @since 1.0.0
     * @param array $actions Current actions
     * @param \WP_Post $post Post object
     * @return array Modified actions
     */
    public function add_post_row_actions(array $actions, \WP_Post $post): array {
        if (!current_user_can('accept_revisions', $post->ID)) {
            return $actions;
        }
        
        // Add link to view pending revisions
        $pending_count = $this->get_post_pending_revisions_count($post->ID);
        
        if ($pending_count > 0) {
            $actions['pending_revisions'] = sprintf(
                '<a href="%s">%s (%d)</a>',
                admin_url("revision.php?post={$post->ID}&pending=1"),
                esc_html__('Pending Revisions', $this->text_domain),
                $pending_count
            );
        }
        
        return $actions;
    }
    
    /**
     * Get total pending revisions count
     *
     * @since 1.0.0
     * @return int
     */
    private function get_pending_revisions_count(): int {
        // This would typically query the database for pending revisions
        // For now, return a placeholder
        return 0;
    }
    
    /**
     * Get pending revisions count for a specific post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int
     */
    private function get_post_pending_revisions_count(int $post_id): int {
        // This would typically query the database for post-specific pending revisions
        // For now, return a placeholder
        return 0;
    }
}