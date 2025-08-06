<?php
/**
 * Post Save Handler
 *
 * @package DGW\PendingRevisions\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Admin;

/**
 * Post Save Handler
 *
 * Handles post saves when in pending approval mode to implement version locking.
 *
 * @since 1.0.0
 */
class Post_Save_Handler {
    
    /**
     * Initialize the post save handler
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        add_action('save_post', [$this, 'handle_post_save'], 5, 3); // Run early
    }
    
    
    /**
     * Handle post save to manage pending revisions
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function handle_post_save(int $post_id, \WP_Post $post, bool $update): void {
        // Skip for autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        
        // Get the editing mode for this post
        $editing_mode = $this->get_post_editing_mode($post_id);
        
        // If this is a new post in pending mode, set initial accepted revision
        if (!$update && $editing_mode === 'pending') {
            update_post_meta($post_id, '_dpr_accepted_revision_id', $post_id);
        }
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
        $default_mode = $settings[$post_type . '_default_editing_mode'] ?? 'open';
        
        return get_post_meta($post_id, '_dgw_editing_mode', true) ?: $default_mode;
    }
}