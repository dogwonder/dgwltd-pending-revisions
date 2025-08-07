<?php
/**
 * Admin Dashboard Handler
 *
 * @package DGW\PendingRevisions\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Admin;

/**
 * Admin Dashboard Handler
 *
 * Handles the main admin dashboard for pending revisions.
 *
 * @since 1.0.0
 */
class Dashboard {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Ensure capabilities are added (fallback if activation didn't work)
        $this->ensure_capabilities();
        
        // Hook directly to admin_menu (bypassing the Loader)
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * Ensure required capabilities exist
     *
     * @since 1.0.0
     * @return void
     */
    private function ensure_capabilities(): void {
        $admin_role = get_role('administrator');
        
        if ($admin_role && !$admin_role->has_cap('accept_revisions')) {
            $admin_role->add_cap('accept_revisions');
            $admin_role->add_cap('manage_pending_revisions');
            $admin_role->add_cap('view_revision_analytics');
        }
        
        $editor_role = get_role('editor');
        
        if ($editor_role && !$editor_role->has_cap('accept_revisions')) {
            $editor_role->add_cap('accept_revisions');
            $editor_role->add_cap('manage_pending_revisions');
        }
    }
    
    /**
     * Add admin menu items
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void {
        // Main pending revisions page
        $page = add_menu_page(
            __('Pending Revisions', 'dgwltd-pending-revisions'),
            __('Pending Revisions', 'dgwltd-pending-revisions'),
            'manage_options',
            'dgw-pending-revisions',
            [$this, 'render_pending_list'],
            'dashicons-backup',
            30
        );
        
        // Hook to load admin assets
        add_action('load-' . $page, [$this, 'load_admin_assets']);
    }
    
    /**
     * Load admin assets
     *
     * @since 1.0.0
     * @return void
     */
    public function load_admin_assets(): void {
        // Only load what we need for the simple table
        wp_enqueue_style('wp-list-table');
    }
    
    /**
     * Render pending revisions list
     *
     * @since 1.0.0
     * @return void
     */
    public function render_pending_list(): void {
        $posts_with_pending = $this->get_posts_with_pending_revisions();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pending Revisions', 'dgwltd-pending-revisions'); ?></h1>
            
            <?php if (empty($posts_with_pending)): ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html__('No posts have pending revisions at the moment.', 'dgwltd-pending-revisions'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-title column-primary">
                                <?php echo esc_html__('Title', 'dgwltd-pending-revisions'); ?>
                            </th>
                            <th scope="col" class="manage-column column-author">
                                <?php echo esc_html__('Author', 'dgwltd-pending-revisions'); ?>
                            </th>
                            <th scope="col" class="manage-column column-current-revision">
                                <?php echo esc_html__('Published Version', 'dgwltd-pending-revisions'); ?>
                            </th>
                            <th scope="col" class="manage-column column-latest-revision">
                                <?php echo esc_html__('Latest Revision ID', 'dgwltd-pending-revisions'); ?>
                            </th>
                            <th scope="col" class="manage-column column-pending-count">
                                <?php echo esc_html__('Pending Revisions', 'dgwltd-pending-revisions'); ?>
                            </th>
                            <th scope="col" class="manage-column column-date">
                                <?php echo esc_html__('Last Modified', 'dgwltd-pending-revisions'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts_with_pending as $post_data): ?>
                            <tr>
                                <td class="title column-title column-primary">
                                    <strong>
                                        <a href="<?php echo esc_url(get_edit_post_link($post_data['post']->ID)); ?>">
                                            <?php echo esc_html($post_data['post']->post_title ?: __('(no title)', 'dgwltd-pending-revisions')); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(get_edit_post_link($post_data['post']->ID)); ?>">
                                                <?php echo esc_html__('Edit', 'dgwltd-pending-revisions'); ?>
                                            </a>
                                        </span>
                                        |
                                        <span class="view">
                                            <a href="<?php echo esc_url(get_permalink($post_data['post']->ID)); ?>" target="_blank">
                                                <?php echo esc_html__('View', 'dgwltd-pending-revisions'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td class="author column-author">
                                    <?php echo esc_html(get_the_author_meta('display_name', $post_data['post']->post_author)); ?>
                                </td>
                                <td class="current-revision column-current-revision">
                                    <?php echo esc_html($post_data['published_version_id'] ?? $post_data['post']->ID); ?>
                                </td>
                                <td class="latest-revision column-latest-revision">
                                    <?php echo esc_html($post_data['latest_revision_id'] ?? 'N/A'); ?>
                                </td>
                                <td class="pending-count column-pending-count">
                                    <span class="count-badge"><?php echo esc_html($post_data['pending_count']); ?></span>
                                </td>
                                <td class="date column-date">
                                    <?php echo esc_html($post_data['last_revision_date']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <style>
                .count-badge {
                    background: #d63638;
                    color: white;
                    padding: 3px 8px;
                    border-radius: 10px;
                    font-size: 11px;
                    font-weight: bold;
                }
                .wp-list-table .column-current-revision,
                .wp-list-table .column-latest-revision,
                .wp-list-table .column-pending-count,
                .wp-list-table .column-date {
                    width: 100px;
                }
                .wp-list-table .column-author {
                    width: 120px;
                }
            </style>
        </div>
        <?php
    }
    
    /**
     * Get posts with pending revisions
     *
     * @since 1.0.0
     * @return array
     */
    private function get_posts_with_pending_revisions(): array {
        global $wpdb;
        
        // Query for posts that have pending revisions
        $query = "
            SELECT DISTINCT p.ID, p.post_title, p.post_author, p.post_modified,
                   COUNT(r.ID) as revision_count,
                   MAX(r.post_modified) as last_revision_date,
                   MAX(r.ID) as latest_revision_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->posts} r ON p.ID = r.post_parent
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_fpr_accepted_revision_id'
            WHERE p.post_status = 'publish'
            AND r.post_type = 'revision'
            AND r.post_status = 'inherit'
            AND (pm.meta_value IS NULL OR pm.meta_value != r.ID)
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm2 
                WHERE pm2.post_id = r.ID 
                AND pm2.meta_key = '_dgw_revision_status' 
                AND pm2.meta_value = 'rejected'
            )
            GROUP BY p.ID
            ORDER BY last_revision_date DESC
        ";
        
        $results = $wpdb->get_results($query);
        $posts_data = [];
        
        foreach ($results as $result) {
            $post = get_post($result->ID);
            if (!$post) continue;
            
            // Get current revision number (total revisions for this post) using efficient count query
            $current_revision_number = $this->get_revision_count($post->ID) + 1; // +1 for the published version
            
            // Get pending revision count
            $pending_count = $this->get_pending_revision_count($post->ID);
            
            // Get published version ID (currently accepted revision)
            $published_version_id = get_post_meta($post->ID, '_fpr_accepted_revision_id', true) ?: $post->ID;
            
            $posts_data[] = [
                'post' => $post,
                'current_revision_number' => $current_revision_number,
                'pending_count' => $pending_count,
                'last_revision_date' => mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $result->last_revision_date),
                'latest_revision_id' => $result->latest_revision_id,
                'published_version_id' => $published_version_id,
            ];
        }
        
        return $posts_data;
    }
    
    /**
     * Get pending revision count for a post
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int
     */
    private function get_pending_revision_count(int $post_id): int {
        // Get all revisions for this post
        $revisions = wp_get_post_revisions($post_id, [
            'posts_per_page' => -1
        ]);
        
        if (empty($revisions)) {
            return 0;
        }
        
        // Get the accepted revision ID for this post
        $accepted_revision_id = get_post_meta($post_id, '_fpr_accepted_revision_id', true);
        
        $pending_count = 0;
        foreach ($revisions as $revision) {
            // Skip if revision is rejected
            $revision_status = get_post_meta($revision->ID, '_dgw_revision_status', true);
            if ($revision_status === 'rejected') {
                continue;
            }
            
            // Count as pending if not accepted
            if (!$accepted_revision_id || $accepted_revision_id != $revision->ID) {
                $pending_count++;
            }
        }
        
        return $pending_count;
    }
    
    /**
     * Get total revision count for a post efficiently
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return int
     */
    private function get_revision_count(int $post_id): int {
        // Use the repository method which now uses WordPress functions
        $repository = new \DGW\PendingRevisions\Database\Revisions_Repository();
        return $repository->get_revision_count($post_id);
    }
}