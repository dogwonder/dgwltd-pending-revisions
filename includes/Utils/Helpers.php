<?php
/**
 * Helper Functions
 *
 * @package DGW\PendingRevisions\Utils
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Utils;

/**
 * Helper Functions
 *
 * Contains utility functions used throughout the plugin.
 *
 * @since 1.0.0
 */
class Helpers {
    
    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_plugin_version(): string {
        return DGW_PENDING_REVISIONS_VERSION ?? '1.0.0';
    }
    
    /**
     * Get plugin directory path
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_plugin_dir(): string {
        return DGW_PENDING_REVISIONS_PLUGIN_DIR;
    }
    
    /**
     * Get plugin URL
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_plugin_url(): string {
        return DGW_PENDING_REVISIONS_PLUGIN_URL;
    }
    
    /**
     * Check if post type supports pending revisions
     *
     * @since 1.0.0
     * @param string $post_type Post type to check
     * @return bool
     */
    public static function is_supported_post_type(string $post_type): bool {
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
    public static function get_post_editing_mode(int $post_id): string {
        $post_type = get_post_type($post_id);
        $settings = get_option('dgw_pending_revisions_settings', []);
        $default_mode = $settings[$post_type . '_default_editing_mode'] ?? 'open';
        
        return get_post_meta($post_id, '_dgw_editing_mode', true) ?: $default_mode;
    }
    
    /**
     * Get accepted revision ID for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int|false
     */
    public static function get_accepted_revision_id(int $post_id): int|false {
        $revision_id = get_post_meta($post_id, '_dpr_accepted_revision_id', true);
        return $revision_id ? absint($revision_id) : false;
    }
    
    /**
     * Set accepted revision ID for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $revision_id Revision ID
     * @return bool
     */
    public static function set_accepted_revision_id(int $post_id, int $revision_id): bool {
        return (bool) update_post_meta($post_id, '_dpr_accepted_revision_id', $revision_id);
    }
    
    /**
     * Get pending revisions count for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int
     */
    public static function get_pending_revisions_count(int $post_id): int {
        $accepted_id = self::get_accepted_revision_id($post_id);
        
        if (!$accepted_id) {
            return 0;
        }
        
        $accepted_post = get_post($accepted_id);
        if (!$accepted_post) {
            return 0;
        }
        
        $revisions = wp_get_post_revisions($post_id, [
            'date_query' => [
                [
                    'after' => $accepted_post->post_date,
                    'inclusive' => false,
                ],
            ],
            'posts_per_page' => -1,
        ]);
        
        return count($revisions);
    }
    
    /**
     * Get editing mode label
     *
     * @since 1.0.0
     * @param string $mode Editing mode
     * @return string
     */
    public static function get_editing_mode_label(string $mode): string {
        $labels = [
            'off' => __('Disabled', 'dgwltd-pending-revisions'),
            'open' => __('Open', 'dgwltd-pending-revisions'),
            'pending' => __('Requires Approval', 'dgwltd-pending-revisions'),
            'locked' => __('Locked', 'dgwltd-pending-revisions'),
        ];
        
        return $labels[$mode] ?? __('Unknown', 'dgwltd-pending-revisions');
    }
    
    /**
     * Get revision status
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $revision_id Revision ID
     * @return string
     */
    public static function get_revision_status(int $post_id, int $revision_id): string {
        $accepted_id = self::get_accepted_revision_id($post_id);
        
        if ($revision_id == $accepted_id) {
            return 'accepted';
        }
        
        $rejected_status = get_metadata('post', $revision_id, '_dgw_revision_status', true);
        if ($rejected_status === 'rejected') {
            return 'rejected';
        }
        
        return 'pending';
    }
    
    /**
     * Log plugin activity
     *
     * @since 1.0.0
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    public static function log(string $message, string $level = 'info'): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("DGW Pending Revisions [{$level}]: {$message}");
        }
    }
    
    /**
     * Format date for display
     *
     * @since 1.0.0
     * @param string $date Date string
     * @param bool $include_time Whether to include time
     * @return string
     */
    public static function format_date(string $date, bool $include_time = true): string {
        $format = get_option('date_format');
        
        if ($include_time) {
            $format .= ' ' . get_option('time_format');
        }
        
        return wp_date($format, strtotime($date));
    }
    
    /**
     * Get human readable time difference
     *
     * @since 1.0.0
     * @param string $date Date string
     * @return string
     */
    public static function time_ago(string $date): string {
        return human_time_diff(strtotime($date), current_time('timestamp')) . ' ' . __('ago', 'dgwltd-pending-revisions');
    }
    
    /**
     * Sanitize and validate editing mode
     *
     * @since 1.0.0
     * @param string $mode Editing mode to validate
     * @return string
     */
    public static function sanitize_editing_mode(string $mode): string {
        $allowed_modes = ['off', 'open', 'pending', 'locked'];
        return in_array($mode, $allowed_modes, true) ? $mode : 'open';
    }
    
    /**
     * Check if user can accept revisions for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int|null $user_id User ID (defaults to current user)
     * @return bool
     */
    public static function user_can_accept_revisions(int $post_id, ?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'accept_revisions', $post_id);
    }
    
    /**
     * Get plugin settings
     *
     * @since 1.0.0
     * @param string|null $key Specific setting key (optional)
     * @return mixed
     */
    public static function get_settings(?string $key = null) {
        $settings = get_option('dgw_pending_revisions_settings', []);
        
        if ($key !== null) {
            return $settings[$key] ?? null;
        }
        
        return $settings;
    }
    
    /**
     * Update plugin settings
     *
     * @since 1.0.0
     * @param array $settings Settings to update
     * @return bool
     */
    public static function update_settings(array $settings): bool {
        return update_option('dgw_pending_revisions_settings', $settings);
    }
}