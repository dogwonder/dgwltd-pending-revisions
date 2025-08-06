<?php
/**
 * Plugin Deactivation Handler
 *
 * @package DGW\PendingRevisions\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Core;

/**
 * Plugin Deactivation Handler
 *
 * Handles tasks that need to be performed when the plugin is deactivated.
 *
 * @since 1.0.0
 */
class Deactivator {
    
    /**
     * Plugin deactivation tasks
     *
     * @since 1.0.0
     * @return void
     */
    public static function deactivate(): void {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
        
        // Clean up temporary data
        self::cleanup_temporary_data();
        
        // Log deactivation
        error_log('DGW Pending Revisions plugin deactivated');
    }
    
    /**
     * Clear scheduled cron jobs
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_cron_jobs(): void {
        // Clear daily cleanup job
        $timestamp = wp_next_scheduled('dgw_pending_revisions_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'dgw_pending_revisions_daily_cleanup');
        }
        
        // Clear weekly analytics job
        $timestamp = wp_next_scheduled('dgw_pending_revisions_weekly_analytics');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'dgw_pending_revisions_weekly_analytics');
        }
        
        // Clear all plugin-related cron jobs
        wp_clear_scheduled_hook('dgw_pending_revisions_daily_cleanup');
        wp_clear_scheduled_hook('dgw_pending_revisions_weekly_analytics');
    }
    
    /**
     * Clean up temporary data
     *
     * @since 1.0.0
     * @return void
     */
    private static function cleanup_temporary_data(): void {
        // Clear any cached data
        wp_cache_flush();
        
        // Delete transients
        delete_transient('dgw_pending_revisions_stats');
        delete_transient('dgw_pending_revisions_cache');
        
        // Clear any temporary files (if any)
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/dgw-pending-revisions-temp/';
        
        if (is_dir($temp_dir)) {
            self::delete_directory($temp_dir);
        }
    }
    
    /**
     * Recursively delete a directory and its contents
     *
     * @since 1.0.0
     * @param string $dir_path Directory path to delete
     * @return bool True on success, false on failure
     */
    private static function delete_directory(string $dir_path): bool {
        if (!is_dir($dir_path)) {
            return false;
        }
        
        $files = array_diff(scandir($dir_path), ['.', '..']);
        
        foreach ($files as $file) {
            $file_path = $dir_path . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($file_path)) {
                self::delete_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        return rmdir($dir_path);
    }
}