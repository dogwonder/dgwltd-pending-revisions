<?php
/**
 * Public Hooks Handler
 *
 * @package DGW\PendingRevisions\Frontend
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Frontend;

/**
 * Public Hooks Handler
 *
 * Handles public-facing functionality and hooks.
 *
 * @since 1.0.0
 */
class Public_Hooks {
    
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
     * Enqueue public styles
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_styles(): void {
        // Only enqueue if needed
        if (!$this->should_enqueue_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'dgwltd-pending-revisions-public',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/public.css',
            [],
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue public scripts
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts(): void {
        // Only enqueue if needed
        if (!$this->should_enqueue_assets()) {
            return;
        }
        
        wp_enqueue_script(
            'dgwltd-pending-revisions-public',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/public.js',
            ['wp-api-fetch', 'wp-i18n'],
            $this->version,
            true
        );
        
        // Localize script with public data
        wp_localize_script(
            'dgwltd-pending-revisions-public',
            'dgwPendingRevisionsPublic',
            [
                'apiUrl' => rest_url('dgw-pending-revisions/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'isUserLoggedIn' => is_user_logged_in(),
                'canEditPosts' => current_user_can('edit_posts'),
                'textDomain' => $this->text_domain,
            ]
        );
        
        // Set script translations
        wp_set_script_translations(
            'dgwltd-pending-revisions-public',
            $this->text_domain,
            DGW_PENDING_REVISIONS_PLUGIN_DIR . 'languages'
        );
    }
    
    /**
     * Check if assets should be enqueued
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_enqueue_assets(): bool {
        // Only enqueue on singular pages with supported post types
        if (!is_singular()) {
            return false;
        }
        
        $post_type = get_post_type();
        return $this->is_supported_post_type($post_type);
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
}