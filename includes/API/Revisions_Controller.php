<?php
/**
 * Revisions REST API Controller
 *
 * @package DGW\PendingRevisions\API
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\API;

/**
 * Revisions REST API Controller
 *
 * Handles REST API endpoints for revision management.
 *
 * @since 1.0.0
 */
class Revisions_Controller extends REST_Controller {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct();
        
        // Set the resource name
        $this->rest_base = 'revisions';
        
        // Hook directly to REST API initialization
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes(): void {
        // Create a pending revision
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_pending_revision'],
                    'permission_callback' => [$this, 'create_revision_permissions_check'],
                    'args' => $this->get_create_revision_params(),
                ],
            ]
        );
        
        // Get revisions for a post
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<post_id>\d+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_revisions'],
                    'permission_callback' => [$this, 'get_revisions_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
            ]
        );
        
        // Get specific revision
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<post_id>\d+)/(?P<revision_id>\d+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_revision'],
                    'permission_callback' => [$this, 'get_revision_permissions_check'],
                ],
            ]
        );
        
        // Approve a revision
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<post_id>\d+)/(?P<revision_id>\d+)/approve',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'approve_revision'],
                    'permission_callback' => [$this, 'approve_revision_permissions_check'],
                ],
            ]
        );
        
        // Reject a revision
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<post_id>\d+)/(?P<revision_id>\d+)/reject',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'reject_revision'],
                    'permission_callback' => [$this, 'reject_revision_permissions_check'],
                ],
            ]
        );
        
        // Get pending revisions summary
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/pending',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_pending_revisions'],
                    'permission_callback' => [$this, 'get_pending_revisions_permissions_check'],
                    'args' => $this->get_collection_params(),
                ],
            ]
        );
        
        // Get revision statistics
        register_rest_route(
            $this->namespace,
            '/stats',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_revision_stats'],
                    'permission_callback' => [$this, 'get_revision_stats_permissions_check'],
                ],
            ]
        );
        
        // Approve revision by ID (simplified endpoint for admin dashboard)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<revision_id>\d+)/approve',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'approve_revision_by_id'],
                    'permission_callback' => [$this, 'approve_revision_permissions_check'],
                ],
            ]
        );
        
        // Reject revision by ID (simplified endpoint for admin dashboard)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<revision_id>\d+)/reject',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'reject_revision_by_id'],
                    'permission_callback' => [$this, 'reject_revision_permissions_check'],
                ],
            ]
        );
        
        // Quick switch published revision (bypass approval workflow)
        register_rest_route(
            $this->namespace,
            '/posts/(?P<post_id>\d+)/set-published-revision',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'quick_switch_published_revision'],
                    'permission_callback' => [$this, 'quick_switch_permissions_check'],
                    'args' => [
                        'revision_id' => [
                            'required' => true,
                            'type' => 'integer',
                            'minimum' => 1,
                        ],
                        'reason' => [
                            'required' => false,
                            'type' => 'string',
                            'maxLength' => 200,
                        ],
                    ],
                ],
            ]
        );
        
        // Emergency revert to last known good revision
        register_rest_route(
            $this->namespace,
            '/posts/(?P<post_id>\d+)/emergency-revert',
            [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'emergency_revert_revision'],
                    'permission_callback' => [$this, 'quick_switch_permissions_check'],
                ],
            ]
        );
        
        // Update post editing mode
        register_rest_route(
            $this->namespace,
            '/posts/(?P<post_id>\d+)/editing-mode',
            [
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_editing_mode'],
                    'permission_callback' => [$this, 'update_editing_mode_permissions_check'],
                    'args' => [
                        'editing_mode' => [
                            'required' => true,
                            'type' => 'string',
                            'enum' => ['open', 'pending', 'locked'],
                        ],
                    ],
                ],
            ]
        );
    }
    
    /**
     * Get revisions for a post
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_revisions(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $revisions = wp_get_post_revisions($post_id, [
            'posts_per_page' => $request['per_page'],
            'paged' => $request['page'],
            'order' => $request['order'],
            'orderby' => $request['orderby'],
        ]);
        
        $data = [];
        $accepted_revision_id = get_post_meta($post_id, '_fpr_accepted_revision_id', true);
        
        foreach ($revisions as $revision) {
            $revision_data = $this->prepare_item_for_response($revision, $request)->get_data();
            $revision_data['is_accepted'] = ($revision->ID == $accepted_revision_id);
            $revision_data['is_pending'] = ($revision->ID != $accepted_revision_id);
            $data[] = $revision_data;
        }
        
        $response = $this->success_response($data);
        $response->header('X-WP-Total', count($revisions));
        $response->header('X-WP-TotalPages', 1); // Simplified for now
        
        return $response;
    }
    
    /**
     * Get specific revision
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_revision(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $revision_id = absint($request['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if (!$revision || $revision->post_parent != $post_id) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        $data = $this->prepare_item_for_response($revision, $request)->get_data();
        $accepted_revision_id = get_post_meta($post_id, '_fpr_accepted_revision_id', true);
        $data['is_accepted'] = ($revision->ID == $accepted_revision_id);
        $data['is_pending'] = ($revision->ID != $accepted_revision_id);
        
        return $this->success_response($data);
    }
    
    /**
     * Approve a revision
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function approve_revision(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $revision_id = absint($request['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if (!$revision || $revision->post_parent != $post_id) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Update the accepted revision ID
        $result = update_post_meta($post_id, '_fpr_accepted_revision_id', $revision_id);
        
        if ($result === false) {
            return $this->error_response(
                'rest_revision_approve_failed',
                esc_html__('Failed to approve revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Log the approval
        $this->log_revision_action($post_id, $revision_id, 'approved', get_current_user_id());
        
        
        return $this->success_response(
            ['revision_id' => $revision_id],
            esc_html__('Revision approved successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Reject a revision
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function reject_revision(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $revision_id = absint($request['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if (!$revision || $revision->post_parent != $post_id) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Mark revision as rejected
        $result = update_metadata('post', $revision_id, '_dgw_revision_status', 'rejected');
        
        if ($result === false) {
            return $this->error_response(
                'rest_revision_reject_failed',
                esc_html__('Failed to reject revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Log the rejection
        $this->log_revision_action($post_id, $revision_id, 'rejected', get_current_user_id());
        
        
        return $this->success_response(
            ['revision_id' => $revision_id],
            esc_html__('Revision rejected successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Get pending revisions
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_pending_revisions(\WP_REST_Request $request) {
        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        
        // Get all revisions using WordPress functions
        $revisions = get_posts([
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'posts_per_page' => -1, // Get all first, then filter
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        $pending_revisions = [];
        
        foreach ($revisions as $revision) {
            // Skip if revision has been rejected
            $revision_status = get_post_meta($revision->ID, '_dgw_revision_status', true);
            if ($revision_status === 'rejected') {
                continue;
            }
            
            // Check if this revision is accepted for its parent post
            if ($revision->post_parent) {
                $accepted_revision_id = get_post_meta($revision->post_parent, '_fpr_accepted_revision_id', true);
                if ($accepted_revision_id && $accepted_revision_id == $revision->ID) {
                    continue; // Skip accepted revisions
                }
            }
            
            // Get parent post title
            $parent_post = get_post($revision->post_parent);
            $parent_title = $parent_post ? $parent_post->post_title : __('(No title)', 'dgwltd-pending-revisions');
            
            // Get author name
            $author = get_user_by('id', $revision->post_author);
            $author_name = $author ? $author->display_name : __('Unknown', 'dgwltd-pending-revisions');
            
            $pending_revisions[] = [
                'id' => $revision->ID,
                'date' => $revision->post_date,
                'modified' => $revision->post_modified,
                'parent' => $revision->post_parent,
                'author' => $revision->post_author,
                'author_name' => $author_name,
                'title' => [
                    'rendered' => $parent_title,
                ],
                'content' => [
                    'rendered' => $revision->post_content,
                ],
                'status' => 'pending',
            ];
        }
        
        // Apply pagination
        $total = count($pending_revisions);
        $offset = ($page - 1) * $per_page;
        $paginated_revisions = array_slice($pending_revisions, $offset, $per_page);
        
        return $this->success_response($paginated_revisions, [
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page,
            'per_page' => $per_page
        ]);
    }
    
    /**
     * Get revision statistics
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_revision_stats(\WP_REST_Request $request) {
        // Use the repository method which now uses WordPress functions
        $repository = new \DGW\PendingRevisions\Database\Revisions_Repository();
        $stats = $repository->get_revision_stats();
        
        // For backwards compatibility, add additional stats that might be expected
        $extended_stats = array_merge($stats, [
            'approved_this_week' => 0,
            'rejected_this_week' => 0,
            'approved_this_month' => 0,
            'rejected_this_month' => 0,
        ]);
        
        return $this->success_response($extended_stats);
    }
    
    /**
     * Approve revision by ID (simplified for admin dashboard)
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function approve_revision_by_id(\WP_REST_Request $request) {
        $revision_id = absint($request['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if (!$revision) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        $post_id = $revision->post_parent;
        
        // Update the accepted revision ID
        $result = update_post_meta($post_id, '_fpr_accepted_revision_id', $revision_id);
        
        if ($result === false) {
            return $this->error_response(
                'rest_revision_approve_failed',
                esc_html__('Failed to approve revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Log the approval
        $this->log_revision_action($post_id, $revision_id, 'approved', get_current_user_id());
        
        
        return $this->success_response(
            ['revision_id' => $revision_id, 'post_id' => $post_id],
            esc_html__('Revision approved successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Reject revision by ID (simplified for admin dashboard)
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function reject_revision_by_id(\WP_REST_Request $request) {
        $revision_id = absint($request['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if (!$revision) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        $post_id = $revision->post_parent;
        
        // Mark revision as rejected
        $result = update_metadata('post', $revision_id, '_dgw_revision_status', 'rejected');
        
        if ($result === false) {
            return $this->error_response(
                'rest_revision_reject_failed',
                esc_html__('Failed to reject revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Log the rejection
        $this->log_revision_action($post_id, $revision_id, 'rejected', get_current_user_id());
        
        
        return $this->success_response(
            ['revision_id' => $revision_id, 'post_id' => $post_id],
            esc_html__('Revision rejected successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Update post editing mode
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_editing_mode(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $editing_mode = sanitize_text_field($request['editing_mode']);
        $result = update_post_meta($post_id, '_dgw_editing_mode', $editing_mode);
        
        if ($result === false) {
            return $this->error_response(
                'rest_editing_mode_update_failed',
                esc_html__('Failed to update editing mode.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        return $this->success_response(
            ['editing_mode' => $editing_mode],
            esc_html__('Editing mode updated successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Prepare item for response
     *
     * @since 1.0.0
     * @param \WP_Post $revision Revision object
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function prepare_item_for_response($revision, $request): \WP_REST_Response {
        $data = [
            'id' => $revision->ID,
            'date' => $revision->post_date,
            'date_gmt' => $revision->post_date_gmt,
            'modified' => $revision->post_modified,
            'modified_gmt' => $revision->post_modified_gmt,
            'parent' => $revision->post_parent,
            'author' => $revision->post_author,
            'author_name' => get_the_author_meta('display_name', $revision->post_author),
            'title' => [
                'rendered' => $revision->post_title,
            ],
            'content' => [
                'rendered' => $revision->post_content,
            ],
            'excerpt' => [
                'rendered' => $revision->post_excerpt,
            ],
            'status' => get_metadata('post', $revision->ID, '_dgw_revision_status', true) ?: 'pending',
        ];
        
        return new \WP_REST_Response($data);
    }
    
    /**
     * Create a pending revision
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_pending_revision(\WP_REST_Request $request) {
        $post_parent = absint($request['post_parent']);
        $post = get_post($post_parent);
        
        if (!$post || $post->post_status === 'auto-draft') {
            return $this->error_response(
                'rest_post_not_found',
                esc_html__('Parent post not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Create revision data
        $revision_data = [
            'post_title' => sanitize_text_field($request['post_title']),
            'post_content' => wp_kses_post($request['post_content']),
            'post_excerpt' => sanitize_text_field($request['post_excerpt']),
            'post_parent' => $post_parent,
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'post_author' => get_current_user_id(),
        ];
        
        // Insert the revision
        $revision_id = wp_insert_post($revision_data, true);
        
        if (is_wp_error($revision_id)) {
            return $this->error_response(
                'rest_revision_create_failed',
                esc_html__('Failed to create revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Mark as pending
        update_metadata('post', $revision_id, '_dgw_revision_status', 'pending');
        
        // Log the creation
        $this->log_revision_action($post_parent, $revision_id, 'created', get_current_user_id());
        
        
        // Trigger action for other plugins
        do_action('dgw_pending_revision_created', $revision_id, $post_parent);
        
        return $this->success_response(
            [
                'revision_id' => $revision_id,
                'post_id' => $post_parent,
                'status' => 'pending'
            ],
            esc_html__('Pending revision created successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Get create revision parameters
     *
     * @since 1.0.0
     * @return array
     */
    public function get_create_revision_params(): array {
        return [
            'post_parent' => [
                'description' => __('The parent post ID.', 'dgwltd-pending-revisions'),
                'type' => 'integer',
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ],
            'post_title' => [
                'description' => __('The title for the revision.', 'dgwltd-pending-revisions'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'post_content' => [
                'description' => __('The content for the revision.', 'dgwltd-pending-revisions'),
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'wp_kses_post',
            ],
            'post_excerpt' => [
                'description' => __('The excerpt for the revision.', 'dgwltd-pending-revisions'),
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
    
    /**
     * Create revision permissions check
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error
     */
    public function create_revision_permissions_check(\WP_REST_Request $request) {
        // Allow users who can edit posts to create pending revisions
        if (!current_user_can('edit_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to create revisions.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        // Check if user can edit the specific post
        $post_parent = absint($request['post_parent']);
        if (!current_user_can('edit_post', $post_parent)) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to edit this post.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Permission checks
     */
    
    public function get_revisions_permissions_check(\WP_REST_Request $request) {
        // Allow more permissive access for reading revisions - any user who can edit posts
        if (!current_user_can('edit_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to view revisions.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    public function get_revision_permissions_check(\WP_REST_Request $request) {
        return $this->check_capability($request, 'edit_posts');
    }
    
    public function approve_revision_permissions_check(\WP_REST_Request $request) {
        return $this->check_capability($request, 'accept_revisions');
    }
    
    public function reject_revision_permissions_check(\WP_REST_Request $request) {
        return $this->check_capability($request, 'accept_revisions');
    }
    
    public function get_pending_revisions_permissions_check(\WP_REST_Request $request) {
        // Allow editors and admins to view pending revisions
        if (!current_user_can('edit_others_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to view pending revisions.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    public function get_revision_stats_permissions_check(\WP_REST_Request $request) {
        // Allow editors and admins to view revision stats
        if (!current_user_can('edit_others_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to view revision statistics.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    public function update_editing_mode_permissions_check(\WP_REST_Request $request) {
        return $this->check_capability($request, 'accept_revisions');
    }
    
    /**
     * Log revision action
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     * @param int $revision_id Revision ID
     * @param string $action Action performed
     * @param int $user_id User who performed the action
     * @return void
     */
    private function log_revision_action(int $post_id, int $revision_id, string $action, int $user_id): void {
        // Use proper WordPress logging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("[INFO] DGW Pending Revisions: User {$user_id} {$action} revision {$revision_id} for post {$post_id}");
        }
    }
    
    
    /**
     * Quick switch published revision (bypass approval workflow)
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object
     */
    public function quick_switch_published_revision(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $revision_id = absint($request['revision_id']);
        $reason = sanitize_text_field($request['reason'] ?? '');
        
        // Validate revision exists and belongs to this post
        $revision = wp_get_post_revision($revision_id);
        if (!$revision || $revision->post_parent != $post_id) {
            return $this->error_response(
                'rest_revision_not_found',
                esc_html__('Revision not found.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Store previous published revision for rollback capability
        $previous_revision_id = get_post_meta($post_id, '_fpr_accepted_revision_id', true);
        if ($previous_revision_id) {
            update_post_meta($post_id, '_dgw_last_known_good', $previous_revision_id);
            // Keep history of last 5 published revisions for emergency rollback
            $revision_history = get_post_meta($post_id, '_dgw_published_history', true) ?: [];
            array_unshift($revision_history, [
                'revision_id' => $previous_revision_id,
                'switched_at' => current_time('mysql'),
                'switched_by' => get_current_user_id(),
                'reason' => $reason,
            ]);
            // Keep only last 5 entries
            $revision_history = array_slice($revision_history, 0, 5);
            update_post_meta($post_id, '_dgw_published_history', $revision_history);
        }
        
        // Set new published revision
        $result = update_post_meta($post_id, '_fpr_accepted_revision_id', $revision_id);
        
        if ($result === false) {
            return $this->error_response(
                'rest_revision_switch_failed',
                esc_html__('Failed to switch published revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Clear any rejection status on the newly published revision
        delete_metadata('post', $revision_id, '_dgw_revision_status');
        
        // Log the switch action with reason
        $log_message = $reason ? "quick switched to revision {$revision_id}: {$reason}" : "quick switched to revision {$revision_id}";
        $this->log_revision_action($post_id, $revision_id, $log_message, get_current_user_id());
        
        // Trigger action for other plugins
        do_action('dgw_pending_revision_quick_switched', $revision_id, $post_id, $previous_revision_id, get_current_user_id());
        
        // Clear any caches
        clean_post_cache($post_id);
        
        return $this->success_response(
            [
                'post_id' => $post_id,
                'revision_id' => $revision_id,
                'previous_revision_id' => $previous_revision_id,
                'reason' => $reason,
            ],
            esc_html__('Published revision switched successfully.', 'dgwltd-pending-revisions')
        );
    }
    
    /**
     * Permission check for quick switch
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error Permission result
     */
    public function quick_switch_permissions_check(\WP_REST_Request $request) {
        return $this->check_capability($request, 'accept_revisions');
    }
    
    /**
     * Emergency revert to last known good revision
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response object
     */
    public function emergency_revert_revision(\WP_REST_Request $request) {
        $post_id = $this->validate_post_id($request['post_id']);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        $last_known_good = get_post_meta($post_id, '_dgw_last_known_good', true);
        if (!$last_known_good) {
            return $this->error_response(
                'rest_no_backup_available',
                esc_html__('No backup revision available for emergency revert.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Validate the backup revision still exists
        $revision = wp_get_post_revision($last_known_good);
        if (!$revision) {
            return $this->error_response(
                'rest_backup_not_found',
                esc_html__('Backup revision no longer exists.', 'dgwltd-pending-revisions'),
                404
            );
        }
        
        // Get current revision for logging
        $current_revision_id = get_post_meta($post_id, '_fpr_accepted_revision_id', true);
        
        // Revert to last known good
        $result = update_post_meta($post_id, '_fpr_accepted_revision_id', $last_known_good);
        
        if ($result === false) {
            return $this->error_response(
                'rest_revert_failed',
                esc_html__('Failed to revert to backup revision.', 'dgwltd-pending-revisions'),
                500
            );
        }
        
        // Log the emergency revert
        $this->log_revision_action($post_id, $last_known_good, "emergency reverted from revision {$current_revision_id}", get_current_user_id());
        
        // Trigger action for other plugins
        do_action('dgw_pending_revision_emergency_reverted', $last_known_good, $post_id, $current_revision_id, get_current_user_id());
        
        // Clear any caches
        clean_post_cache($post_id);
        
        return $this->success_response(
            [
                'post_id' => $post_id,
                'reverted_to_revision_id' => $last_known_good,
                'previous_revision_id' => $current_revision_id,
            ],
            esc_html__('Emergency revert completed successfully.', 'dgwltd-pending-revisions')
        );
    }
}