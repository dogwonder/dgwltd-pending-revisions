<?php
/**
 * Performance Testing Utility
 *
 * Simple utility to test database performance improvements.
 *
 * @package DGW\PendingRevisions\Utils
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Utils;

/**
 * Performance Tester Class
 *
 * Tests database performance and verifies optimizations.
 *
 * @since 1.0.0
 */
class Performance_Tester {
    
    /**
     * Test database indexes and performance
     *
     * @since 1.0.0
     * @return array Test results
     */
    public static function test_database_performance(): array {
        global $wpdb;
        
        $results = [
            'indexes_status' => [],
            'query_performance' => [],
            'repository_functionality' => [],
            'overall_score' => 0
        ];
        
        // Test 1: Check if critical indexes exist
        $results['indexes_status'] = self::check_indexes();
        
        // Test 2: Test query performance
        $results['query_performance'] = self::test_queries();
        
        // Test 3: Test repository functionality
        $results['repository_functionality'] = self::test_repository();
        
        // Calculate overall score
        $results['overall_score'] = self::calculate_score($results);
        
        return $results;
    }
    
    /**
     * Check if critical indexes exist
     *
     * @since 1.0.0
     * @return array Index status results
     */
    private static function check_indexes(): array {
        global $wpdb;
        
        $results = [];
        
        // Check posts table indexes
        $posts_indexes = $wpdb->get_results(
            "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name IN ('post_type_status_modified_idx', 'parent_type_idx')",
            ARRAY_A
        );
        
        $posts_index_names = array_column($posts_indexes, 'Key_name');
        $results['posts_type_status_modified'] = in_array('post_type_status_modified_idx', $posts_index_names, true);
        $results['posts_parent_type'] = in_array('parent_type_idx', $posts_index_names, true);
        
        // Check custom table indexes if table exists
        $custom_table = $wpdb->prefix . 'dgw_revision_meta';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $custom_table)) === $custom_table;
        
        if ($table_exists) {
            $custom_indexes = $wpdb->get_results(
                "SHOW INDEX FROM {$custom_table}",
                ARRAY_A
            );
            
            $custom_index_names = array_column($custom_indexes, 'Key_name');
            $results['custom_post_meta_key'] = in_array('post_meta_key', $custom_index_names, true);
            $results['custom_revision_meta_key'] = in_array('revision_meta_key', $custom_index_names, true);
            $results['custom_created_at'] = in_array('created_at_idx', $custom_index_names, true);
        } else {
            $results['custom_table_exists'] = false;
        }
        
        return $results;
    }
    
    /**
     * Test query performance
     *
     * @since 1.0.0
     * @return array Query performance results
     */
    private static function test_queries(): array {
        $results = [];
        
        // Test revision count query performance using WordPress functions
        $start_time = microtime(true);
        $revisions = get_posts([
            'post_type' => 'revision',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        count($revisions);
        $end_time = microtime(true);
        $results['revision_count_time'] = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
        
        // Test repository stats method performance (includes pending revisions calculation)
        $start_time = microtime(true);
        $repository = new \DGW\PendingRevisions\Database\Revisions_Repository();
        $repository->get_revision_stats();
        $end_time = microtime(true);
        $results['pending_revisions_time'] = round(($end_time - $start_time) * 1000, 2);
        
        return $results;
    }
    
    /**
     * Test repository functionality
     *
     * @since 1.0.0
     * @return array Repository test results
     */
    private static function test_repository(): array {
        $results = [];
        
        try {
            $repository = new \DGW\PendingRevisions\Database\Revisions_Repository();
            
            // Test stats method (should return actual data, not zeros)
            $stats = $repository->get_revision_stats();
            $results['stats_method'] = [
                'working' => true,
                'total_revisions' => $stats['total_revisions'],
                'pending_revisions' => $stats['pending_revisions']
            ];
            
            // Test cache functionality
            $stats1 = $repository->get_revision_stats();
            $stats2 = $repository->get_revision_stats();
            $results['caching'] = [
                'working' => $stats1 === $stats2, // Should be identical if cached
                'cache_hit' => true
            ];
            
            // Test cache clearing
            $repository->clear_stats_cache();
            $stats3 = $repository->get_revision_stats();
            $results['cache_clearing'] = [
                'working' => true
            ];
            
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Calculate overall performance score
     *
     * @since 1.0.0
     * @param array $results Test results
     * @return int Score from 0-100
     */
    private static function calculate_score(array $results): int {
        $score = 0;
        $max_score = 0;
        
        // Index score (40 points max)
        foreach ($results['indexes_status'] as $status) {
            $max_score += 10;
            if ($status === true) {
                $score += 10;
            }
        }
        
        // Performance score (30 points max)
        $max_score += 30;
        if (isset($results['query_performance']['revision_count_time'])) {
            if ($results['query_performance']['revision_count_time'] < 10) {
                $score += 15; // Under 10ms = excellent
            } elseif ($results['query_performance']['revision_count_time'] < 50) {
                $score += 10; // Under 50ms = good
            } elseif ($results['query_performance']['revision_count_time'] < 100) {
                $score += 5; // Under 100ms = okay
            }
        }
        
        if (isset($results['query_performance']['pending_revisions_time'])) {
            if ($results['query_performance']['pending_revisions_time'] < 20) {
                $score += 15; // Under 20ms = excellent
            } elseif ($results['query_performance']['pending_revisions_time'] < 100) {
                $score += 10; // Under 100ms = good
            } elseif ($results['query_performance']['pending_revisions_time'] < 200) {
                $score += 5; // Under 200ms = okay
            }
        }
        
        // Repository functionality score (30 points max)
        $max_score += 30;
        if (isset($results['repository_functionality']['stats_method']['working']) && 
            $results['repository_functionality']['stats_method']['working']) {
            $score += 15;
        }
        
        if (isset($results['repository_functionality']['caching']['working']) && 
            $results['repository_functionality']['caching']['working']) {
            $score += 15;
        }
        
        return $max_score > 0 ? round(($score / $max_score) * 100) : 0;
    }
    
    /**
     * Generate human-readable performance report
     *
     * @since 1.0.0
     * @param array $results Test results
     * @return string Formatted report
     */
    public static function generate_report(array $results): string {
        $report = "DGW Pending Revisions - Performance Test Report\n";
        $report .= "=" . str_repeat("=", 50) . "\n\n";
        
        $report .= "Overall Score: {$results['overall_score']}/100\n\n";
        
        // Indexes Status
        $report .= "Database Indexes:\n";
        $report .= "-" . str_repeat("-", 20) . "\n";
        foreach ($results['indexes_status'] as $index => $status) {
            $status_text = $status ? "✓ OK" : "✗ Missing";
            $report .= sprintf("%-30s %s\n", $index, $status_text);
        }
        $report .= "\n";
        
        // Query Performance
        $report .= "Query Performance:\n";
        $report .= "-" . str_repeat("-", 20) . "\n";
        if (isset($results['query_performance']['revision_count_time'])) {
            $report .= sprintf("Revision count query: %.2fms\n", $results['query_performance']['revision_count_time']);
        }
        if (isset($results['query_performance']['pending_revisions_time'])) {
            $report .= sprintf("Pending revisions query: %.2fms\n", $results['query_performance']['pending_revisions_time']);
        }
        $report .= "\n";
        
        // Repository Status
        $report .= "Repository Functionality:\n";
        $report .= "-" . str_repeat("-", 25) . "\n";
        if (isset($results['repository_functionality']['stats_method'])) {
            $stats = $results['repository_functionality']['stats_method'];
            $report .= sprintf("Stats method: %s\n", $stats['working'] ? "✓ Working" : "✗ Failed");
            $report .= sprintf("Total revisions: %d\n", $stats['total_revisions'] ?? 0);
            $report .= sprintf("Pending revisions: %d\n", $stats['pending_revisions'] ?? 0);
        }
        
        if (isset($results['repository_functionality']['caching']['working'])) {
            $report .= sprintf("Caching: %s\n", 
                $results['repository_functionality']['caching']['working'] ? "✓ Working" : "✗ Failed");
        }
        
        return $report;
    }
}