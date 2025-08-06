<?php
/**
 * Admin Meta Boxes Handler
 *
 * @package DGW\PendingRevisions\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Admin;

/**
 * Admin Meta Boxes Handler
 *
 * Handles meta boxes for post editing screens.
 *
 * @since 1.0.0
 */
class Meta_Boxes {
    
    /**
     * Add meta boxes
     *
     * @since 1.0.0
     * @return void
     */
    public function add_meta_boxes(): void {
        $post_types = $this->get_supported_post_types();
        
        foreach ($post_types as $post_type) {
            // Editing permissions meta box (for users with appropriate capabilities)
            if (current_user_can('accept_revisions')) {
                add_meta_box(
                    'dgw-editing-permissions',
                    __('Editing Permissions', 'dgwltd-pending-revisions'),
                    [$this, 'render_editing_permissions_meta_box'],
                    $post_type,
                    'side',
                    'high'
                );
            }
            
            // Revision history meta box (snapshot only)
            add_meta_box(
                'dgw-revision-history',
                __('Revision History', 'dgwltd-pending-revisions'),
                [$this, 'render_revision_history_meta_box'],
                $post_type,
                'normal',
                'default'
            );
        }
    }
    
    
    /**
     * Render editing permissions meta box
     *
     * @since 1.0.0
     * @param \WP_Post $post Current post object
     * @return void
     */
    public function render_editing_permissions_meta_box(\WP_Post $post): void {
        if (!current_user_can('accept_revisions', $post->ID)) {
            return;
        }
        
        wp_nonce_field('dgw_editing_permissions_meta_box', 'dgw_editing_permissions_nonce');
        
        $current_mode = $this->get_post_editing_mode($post->ID);
        $modes = [
            'open' => __('Open - All changes published immediately', 'dgwltd-pending-revisions'),
            'pending' => __('Requires Approval - Changes need approval and current version is locked until approval', 'dgwltd-pending-revisions'),
        ];
        
        ?>
        <div class="dgw-editing-permissions">
            <p><?php echo esc_html__('Override the default editing mode for this post:', 'dgwltd-pending-revisions'); ?></p>
            
            <?php foreach ($modes as $mode => $description) : ?>
            <label class="dgw-mode-option">
                <input type="radio" name="dgw_editing_mode" value="<?php echo esc_attr($mode); ?>" <?php checked($current_mode, $mode); ?>>
                <strong><?php echo esc_html(ucfirst($mode)); ?></strong><br>
                <small><?php echo esc_html($description); ?></small>
            </label>
            <?php endforeach; ?>
        </div>
        
        <style>
        .dgw-editing-permissions .dgw-mode-option {
            display: block;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .dgw-editing-permissions .dgw-mode-option:hover {
            background: #f9f9f9;
        }
        .dgw-editing-permissions input[type="radio"] {
            margin-right: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Render revision history meta box
     *
     * @since 1.0.0
     * @param \WP_Post $post Current post object
     * @return void
     */
    public function render_revision_history_meta_box(\WP_Post $post): void {
        $revisions = $this->get_post_revisions($post->ID);
        $accepted_revision_id = get_post_meta($post->ID, '_dpr_accepted_revision_id', true);
        
        ?>
        <div class="dgw-revision-history">
            <?php if (empty($revisions)) : ?>
                <p><?php echo esc_html__('No revisions found.', 'dgwltd-pending-revisions'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Revision ID', 'dgwltd-pending-revisions'); ?></th>
                            <th><?php echo esc_html__('Date', 'dgwltd-pending-revisions'); ?></th>
                            <th><?php echo esc_html__('Author', 'dgwltd-pending-revisions'); ?></th>
                            <th><?php echo esc_html__('Status', 'dgwltd-pending-revisions'); ?></th>
                            <th><?php echo esc_html__('Actions', 'dgwltd-pending-revisions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisions as $revision) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url("revision.php?revision={$revision->ID}")); ?>" target="_blank" rel="noopener noreferrer">
                                    #<?php echo esc_html($revision->ID); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($revision->post_date))); ?>
                            </td>
                            <td>
                                <?php echo esc_html(get_the_author_meta('display_name', $revision->post_author)); ?>
                            </td>
                            <td>
                                <?php 
                                $revision_status = get_metadata('post', $revision->ID, '_dgw_revision_status', true);
                                if ($revision->ID == $accepted_revision_id) : ?>
                                    <span class="dgw-status-badge dgw-status-published"><?php echo esc_html__('Published', 'dgwltd-pending-revisions'); ?></span>
                                <?php elseif ($revision_status === 'rejected') : ?>
                                    <span class="dgw-status-badge dgw-status-rejected"><?php echo esc_html__('Rejected', 'dgwltd-pending-revisions'); ?></span>
                                <?php else : ?>
                                    <span class="dgw-status-badge dgw-status-pending"><?php echo esc_html__('Pending', 'dgwltd-pending-revisions'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $is_current = ($revision->ID == $accepted_revision_id); ?>
                                <?php $can_quick_switch = current_user_can('accept_revisions', $post->ID) && !$is_current; ?>
                                
                                <div class="dgw-revision-actions">
                                    <?php if ($is_current) : ?>
                                        <span class="dgw-current-badge"><?php echo esc_html__('✓ Current', 'dgwltd-pending-revisions'); ?></span>
                                    <?php elseif ($can_quick_switch) : ?>
                                        <button type="button" 
                                                class="button button-small dgw-quick-switch-btn <?php echo $revision_status === 'rejected' ? 'button-secondary' : 'button-primary'; ?>" 
                                                data-post-id="<?php echo esc_attr($post->ID); ?>"
                                                data-revision-id="<?php echo esc_attr($revision->ID); ?>"
                                                data-revision-status="<?php echo esc_attr($revision_status ?: 'pending'); ?>">
                                            <?php if ($revision_status === 'rejected') : ?>
                                                🔄 <?php echo esc_html__('Republish', 'dgwltd-pending-revisions'); ?>
                                            <?php else : ?>
                                                <?php echo esc_html__('Set as Published', 'dgwltd-pending-revisions'); ?>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url(admin_url("revision.php?revision={$revision->ID}")); ?>" 
                                       class="button button-small" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <?php echo esc_html__('Compare', 'dgwltd-pending-revisions'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return void
     */
    public function save_meta_boxes(int $post_id): void {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Verify nonces - only proceed if the nonce exists and is valid
        if (!isset($_POST['dgw_editing_permissions_nonce']) || 
            !wp_verify_nonce($_POST['dgw_editing_permissions_nonce'], 'dgw_editing_permissions_meta_box')) {
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('accept_revisions', $post_id)) {
            return;
        }
        
        // Check if this post type is supported
        $post_type = get_post_type($post_id);
        $supported_post_types = $this->get_supported_post_types();
        if (!in_array($post_type, $supported_post_types, true)) {
            return;
        }
        
        // Save editing mode
        if (isset($_POST['dgw_editing_mode'])) {
            $allowed_modes = ['open', 'pending'];
            $editing_mode = sanitize_text_field($_POST['dgw_editing_mode']);
            
            if (in_array($editing_mode, $allowed_modes, true)) {
                update_post_meta($post_id, '_dgw_editing_mode', $editing_mode);
            }
        }
    }
    
    /**
     * Get supported post types
     *
     * @since 1.0.0
     * @return array
     */
    private function get_supported_post_types(): array {
        $settings = get_option('dgw_pending_revisions_settings', []);
        $post_types = get_post_types(['public' => true]);
        $supported = [];
        
        foreach ($post_types as $post_type) {
            if ($post_type === 'attachment') {
                continue;
            }
            
            $setting_key = $post_type . '_default_editing_mode';
            $mode = $settings[$setting_key] ?? 'off';
            
            if ($mode !== 'off') {
                $supported[] = $post_type;
            }
        }
        
        return $supported;
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
     * Get revision status
     *
     * @since 1.0.0
     * @param \WP_Post $post Post object
     * @return string
     */
    private function get_revision_status(\WP_Post $post): string {
        $pending_count = $this->get_pending_revisions_count($post->ID);
        
        if ($pending_count > 0) {
            return 'pending';
        }
        
        return 'published';
    }
    
    /**
     * Get revision status label
     *
     * @since 1.0.0
     * @param \WP_Post $post Post object
     * @return string
     */
    private function get_revision_status_label(\WP_Post $post): string {
        $status = $this->get_revision_status($post);
        
        $labels = [
            'published' => __('Published', 'dgwltd-pending-revisions'),
            'pending' => __('Has Pending', 'dgwltd-pending-revisions'),
        ];
        
        return $labels[$status] ?? __('Unknown', 'dgwltd-pending-revisions');
    }
    
    /**
     * Get editing mode label
     *
     * @since 1.0.0
     * @param string $mode Editing mode
     * @return string
     */
    private function get_editing_mode_label(string $mode): string {
        $labels = [
            'open' => __('Open', 'dgwltd-pending-revisions'),
            'pending' => __('Requires Approval', 'dgwltd-pending-revisions'),
        ];
        
        return $labels[$mode] ?? __('Unknown', 'dgwltd-pending-revisions');
    }
    
    /**
     * Get latest revision
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return \WP_Post|null
     */
    private function get_latest_revision(int $post_id): ?\WP_Post {
        $revisions = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        return $revisions ? reset($revisions) : null;
    }
    
    /**
     * Get pending revisions count
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int
     */
    private function get_pending_revisions_count(int $post_id): int {
        // This would typically query for pending revisions
        // For now, return placeholder
        return 0;
    }
    
    /**
     * Get post revisions
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array
     */
    private function get_post_revisions(int $post_id): array {
        return wp_get_post_revisions($post_id, ['posts_per_page' => 10]);
    }
}