<?php
/**
 * Capabilities Handler
 *
 * @package DGW\PendingRevisions\Utils
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Utils;

/**
 * Capabilities Handler
 *
 * Manages custom capabilities for the plugin.
 *
 * @since 1.0.0
 */
class Capabilities {
    
    /**
     * Custom capabilities
     *
     * @since 1.0.0
     * @var array
     */
    private array $capabilities = [
        'accept_revisions',
        'manage_pending_revisions',
        'view_revision_analytics',
    ];
    
    /**
     * Add capabilities to roles
     *
     * @since 1.0.0
     * @return void
     */
    public function add_capabilities(): void {
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($this->capabilities as $capability) {
                $admin_role->add_cap($capability);
            }
        }
        
        // Add limited capabilities to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('accept_revisions');
            $editor_role->add_cap('manage_pending_revisions');
        }
        
        // Add view-only capability to author role for their own content
        add_filter('user_has_cap', [$this, 'maybe_grant_author_capabilities'], 10, 4);
    }
    
    /**
     * Remove capabilities from roles
     *
     * @since 1.0.0
     * @return void
     */
    public function remove_capabilities(): void {
        $roles = ['administrator', 'editor', 'author'];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($this->capabilities as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }
    
    /**
     * Maybe grant capabilities to authors for their own content
     *
     * @since 1.0.0
     * @param array $allcaps All capabilities for the user
     * @param array $caps Required capabilities
     * @param array $args Additional arguments
     * @param \WP_User $user User object
     * @return array
     */
    public function maybe_grant_author_capabilities(array $allcaps, array $caps, array $args, \WP_User $user): array {
        // Only proceed if checking for our custom capabilities
        if (empty($caps) || !in_array($caps[0], $this->capabilities, true)) {
            return $allcaps;
        }
        
        // If user already has the capability, return as is
        if (!empty($allcaps[$caps[0]])) {
            return $allcaps;
        }
        
        // Check if this is for a specific post and user is the author
        if (isset($args[2])) {
            $post_id = absint($args[2]);
            $post = get_post($post_id);
            
            if ($post && $post->post_author == $user->ID) {
                // Grant limited capabilities for their own content
                switch ($caps[0]) {
                    case 'accept_revisions':
                        // Only editors and administrators can approve/reject revisions
                        // This is part of the editorial workflow and should not be granted to authors
                        break;
                        
                    case 'manage_pending_revisions':
                        // Authors can manage their own pending revisions (view, create)
                        $allcaps[$caps[0]] = true;
                        break;
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Check if user can perform action on post
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param string $capability Required capability
     * @param int $post_id Post ID
     * @return bool
     */
    public function user_can_perform_action(int $user_id, string $capability, int $post_id): bool {
        return user_can($user_id, $capability, $post_id);
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
    
    /**
     * Get all custom capabilities
     *
     * @since 1.0.0
     * @return array
     */
    public function get_capabilities(): array {
        return $this->capabilities;
    }
    
    /**
     * Check if capability is plugin-related
     *
     * @since 1.0.0
     * @param string $capability Capability to check
     * @return bool
     */
    public function is_plugin_capability(string $capability): bool {
        return in_array($capability, $this->capabilities, true);
    }
}