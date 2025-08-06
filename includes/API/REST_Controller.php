<?php
/**
 * Base REST API Controller
 *
 * @package DGW\PendingRevisions\API
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\API;

/**
 * Base REST API Controller
 *
 * Provides common functionality for all REST API controllers.
 *
 * @since 1.0.0
 */
class REST_Controller extends \WP_REST_Controller {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Set the namespace for our API
        $this->namespace = 'dgw-pending-revisions/v1';
    }
    
    /**
     * Check if user has required capability
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @param string $capability Required capability
     * @return bool|\WP_Error
     */
    protected function check_capability(\WP_REST_Request $request, string $capability) {
        if (!current_user_can($capability)) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to perform this action.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Verify nonce for security
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error
     */
    protected function verify_nonce(\WP_REST_Request $request) {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'rest_invalid_nonce',
                esc_html__('Invalid nonce.', 'dgwltd-pending-revisions'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize and validate post ID
     *
     * @since 1.0.0
     * @param mixed $post_id Post ID to validate
     * @return int|\WP_Error
     */
    protected function validate_post_id($post_id) {
        $post_id = absint($post_id);
        
        if (!$post_id) {
            return new \WP_Error(
                'rest_invalid_post_id',
                esc_html__('Invalid post ID.', 'dgwltd-pending-revisions'),
                ['status' => 400]
            );
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error(
                'rest_post_not_found',
                esc_html__('Post not found.', 'dgwltd-pending-revisions'),
                ['status' => 404]
            );
        }
        
        return $post_id;
    }
    
    /**
     * Format error response
     *
     * @since 1.0.0
     * @param string $code Error code
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array $data Additional error data
     * @return \WP_Error
     */
    protected function error_response(string $code, string $message, int $status = 400, array $data = []): \WP_Error {
        return new \WP_Error($code, $message, array_merge(['status' => $status], $data));
    }
    
    /**
     * Format success response
     *
     * @since 1.0.0
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $status HTTP status code
     * @return \WP_REST_Response
     */
    protected function success_response($data = null, string $message = '', int $status = 200): \WP_REST_Response {
        $response_data = [
            'success' => true,
        ];
        
        if ($message) {
            $response_data['message'] = $message;
        }
        
        if ($data !== null) {
            $response_data['data'] = $data;
        }
        
        return new \WP_REST_Response($response_data, $status);
    }
    
    /**
     * Get common response headers
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_response_headers(): array {
        return [
            'X-DGW-PR-Version' => DGW_PENDING_REVISIONS_VERSION,
            'X-DGW-PR-Timestamp' => current_time('timestamp'),
        ];
    }
    
    /**
     * Prepare item for response
     * 
     * This method should be overridden by child classes to provide
     * specific item preparation logic.
     *
     * @since 1.0.0
     * @param mixed $item Item to prepare
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response
     */
    public function prepare_item_for_response($item, $request) {
        // Default implementation - child classes should override this
        $data = [];
        
        if (is_object($item) && isset($item->ID)) {
            $data['id'] = $item->ID;
        }
        
        return new \WP_REST_Response($data);
    }
    
    /**
     * Get collection parameters
     *
     * @since 1.0.0
     * @return array
     */
    public function get_collection_params(): array {
        return [
            'page' => [
                'description' => esc_html__('Current page of the collection.', 'dgwltd-pending-revisions'),
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
            'per_page' => [
                'description' => esc_html__('Maximum number of items to be returned in result set.', 'dgwltd-pending-revisions'),
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'search' => [
                'description' => esc_html__('Limit results to those matching a string.', 'dgwltd-pending-revisions'),
                'type' => 'string',
            ],
            'order' => [
                'description' => esc_html__('Order sort attribute ascending or descending.', 'dgwltd-pending-revisions'),
                'type' => 'string',
                'default' => 'desc',
                'enum' => ['asc', 'desc'],
            ],
            'orderby' => [
                'description' => esc_html__('Sort collection by object attribute.', 'dgwltd-pending-revisions'),
                'type' => 'string',
                'default' => 'date',
                'enum' => ['date', 'id', 'title', 'author'],
            ],
        ];
    }
}