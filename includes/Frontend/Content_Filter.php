<?php
/**
 * Content Filter Handler
 *
 * @package DGW\PendingRevisions\Frontend
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Frontend;

/**
 * Content Filter Handler
 *
 * Filters frontend content to display accepted revisions instead of latest content.
 * Compatible with the original fabrica-pending-revisions plugin data structure.
 *
 * @since 1.0.0
 */
class Content_Filter {
    
    /**
     * Filter post content
     *
     * @since 1.0.0
     * @param string $content Post content
     * @return string
     */
    public function filter_content(string $content): string {
        if (is_preview() && !isset($_GET['fpr-preview'])) {
            return $content;
        }
        
        $post_id = get_queried_object_id();
        if (!$post_id || !$this->is_supported_post_type(get_post_type($post_id))) {
            return $content;
        }
        
        // Handle preview of specific revision
        if (isset($_GET['fpr-preview']) && is_numeric($_GET['fpr-preview']) && current_user_can('edit_posts', $post_id)) {
            $preview_content = get_post_field('post_content', absint($_GET['fpr-preview']));
            return $preview_content ?: $content;
        }
        
        // Get accepted revision content
        $accepted_id = get_post_meta($post_id, '_dpr_accepted_revision_id', true);
        if (!$accepted_id || $accepted_id == $post_id) {
            return $content;
        }
        
        $accepted_content = get_post_field('post_content', $accepted_id);
        return $accepted_content ?: $content;
    }
    
    /**
     * Filter post title
     *
     * @since 1.0.0
     * @param string $title Post title
     * @param int|\WP_Post $post Post ID or object
     * @return string
     */
    public function filter_title(string $title, $post = null): string {
        if (is_preview() && !isset($_GET['fpr-preview'])) {
            return $title;
        }
        
        $post_id = is_object($post) ? $post->ID : $post;
        if (!$post_id || !$this->is_supported_post_type(get_post_type($post_id))) {
            return $title;
        }
        
        // Handle preview of specific revision
        if (isset($_GET['fpr-preview']) && is_numeric($_GET['fpr-preview']) && current_user_can('edit_posts', $post_id)) {
            $preview_title = get_post_field('post_title', absint($_GET['fpr-preview']));
            return $preview_title ?: $title;
        }
        
        // Get accepted revision title
        $accepted_id = get_post_meta($post_id, '_dpr_accepted_revision_id', true);
        if (!$accepted_id || $accepted_id == $post_id) {
            return $title;
        }
        
        $accepted_title = get_post_field('post_title', $accepted_id);
        return $accepted_title ?: $title;
    }
    
    /**
     * Filter post excerpt
     *
     * @since 1.0.0
     * @param string $excerpt Post excerpt
     * @return string
     */
    public function filter_excerpt(string $excerpt): string {
        if (is_preview() && !isset($_GET['fpr-preview'])) {
            return $excerpt;
        }
        
        $post_id = get_queried_object_id();
        if (!$post_id || !$this->is_supported_post_type(get_post_type($post_id))) {
            return $excerpt;
        }
        
        // Handle preview of specific revision
        if (isset($_GET['fpr-preview']) && is_numeric($_GET['fpr-preview']) && current_user_can('edit_posts', $post_id)) {
            $preview_excerpt = get_post_field('post_excerpt', absint($_GET['fpr-preview']));
            return $preview_excerpt ?: $excerpt;
        }
        
        // Get accepted revision excerpt
        $accepted_id = get_post_meta($post_id, '_dpr_accepted_revision_id', true);
        if (!$accepted_id || $accepted_id == $post_id) {
            return $excerpt;
        }
        
        $accepted_excerpt = get_post_field('post_excerpt', $accepted_id);
        return $accepted_excerpt ?: $excerpt;
    }
    
    /**
     * Filter post metadata
     *
     * @since 1.0.0
     * @param mixed $value Metadata value
     * @param int $object_id Object ID
     * @param string $meta_key Metadata key
     * @param bool $single Whether to return single value
     * @return mixed
     */
    public function filter_meta($value, int $object_id, string $meta_key, bool $single) {
        if (is_preview() && !isset($_GET['fpr-preview'])) {
            return $value;
        }
        
        if (!$this->is_supported_post_type(get_post_type($object_id))) {
            return $value;
        }
        
        // Don't filter our own plugin meta
        if (strpos($meta_key, '_fpr_') === 0 || strpos($meta_key, '_dgw_') === 0) {
            return $value;
        }
        
        // Handle preview of specific revision
        if (isset($_GET['fpr-preview']) && is_numeric($_GET['fpr-preview']) && current_user_can('edit_posts', $object_id)) {
            return get_metadata('post', absint($_GET['fpr-preview']), $meta_key, $single);
        }
        
        // Get accepted revision metadata
        $accepted_id = get_post_meta($object_id, '_dpr_accepted_revision_id', true);
        if (!$accepted_id || $accepted_id == $object_id) {
            return $value;
        }
        
        // Special handling for featured image
        if ($meta_key === '_thumbnail_id') {
            $accepted_thumbnail = get_post_meta($accepted_id, '_thumbnail_id', $single);
            return $accepted_thumbnail ?: $value;
        }
        
        // Get metadata from accepted revision
        return get_metadata('post', $accepted_id, $meta_key, $single);
    }
    
    /**
     * Filter object terms (taxonomies)
     *
     * @since 1.0.0
     * @param array $terms Array of terms
     * @param array $object_ids Object IDs
     * @param array $taxonomies Taxonomies
     * @param array $args Query arguments
     * @return array
     */
    public function filter_terms(array $terms, array $object_ids, array $taxonomies, array $args): array {
        if (is_preview() && !isset($_GET['fpr-preview'])) {
            return $terms;
        }
        
        if (!is_array($object_ids)) {
            $object_ids = [$object_ids];
        }
        
        $accepted_revisions = [];
        $preview_terms = [];
        $preview_post = false;
        
        if (isset($_GET['fpr-preview']) && is_numeric($_GET['fpr-preview'])) {
            $preview_post = wp_get_post_parent_id(absint($_GET['fpr-preview']));
        }
        
        foreach ($object_ids as $object_id) {
            if (empty($object_id) || !$this->is_supported_post_type(get_post_type($object_id))) {
                continue;
            }
            
            // Handle preview of specific revision
            if ($preview_post && $preview_post == $object_id && current_user_can('edit_posts', $object_id)) {
                $preview_terms = wp_get_object_terms(absint($_GET['fpr-preview']), $taxonomies, $args);
                continue;
            }
            
            // Get accepted revision
            $accepted_id = get_post_meta($object_id, '_dpr_accepted_revision_id', true);
            if (!$accepted_id || $accepted_id == $object_id) {
                continue;
            }
            
            $accepted_revisions[$accepted_id] = $object_id;
        }
        
        if (empty($accepted_revisions) && empty($preview_terms)) {
            return $terms;
        }
        
        // Get terms for accepted revisions
        $revision_terms = [];
        if (!empty(array_keys($accepted_revisions))) {
            $revision_terms = wp_get_object_terms(array_keys($accepted_revisions), $taxonomies, $args);
        }
        
        // Merge with preview terms
        $revision_terms = array_merge($preview_terms, $revision_terms);
        
        // Update object IDs if needed
        if (isset($args['fields']) && $args['fields'] === 'all_with_object_id') {
            foreach ($revision_terms as $term) {
                if ($preview_post && isset($_GET['fpr-preview']) && $term->object_id == absint($_GET['fpr-preview'])) {
                    $term->object_id = $preview_post;
                } elseif (isset($accepted_revisions[$term->object_id])) {
                    $term->object_id = $accepted_revisions[$term->object_id];
                }
            }
        }
        
        // Filter out original terms and return revised ones
        $filtered_terms = [];
        $replaced_objects = array_merge([$preview_post], array_values($accepted_revisions));
        
        foreach ($terms as $term) {
            if (isset($term->object_id) && in_array($term->object_id, $replaced_objects, true)) {
                continue;
            }
            $filtered_terms[] = $term;
        }
        
        return array_merge($filtered_terms, $revision_terms);
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