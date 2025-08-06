<?php
/**
 * Plugin Name:         DGW.ltd Pending Revisions
 * Plugin URI:          https://dgw.ltd/
 * Description:         Modern WordPress plugin for managing pending revisions and draft workflows. Enables content versioning with published drafts, allowing editing of new versions while keeping published versions at earlier iterations. Built with modern WordPress standards, Block Editor integration, and React-based admin interfaces.
 * Version:             1.0.0
 * Requires at least:   6.0
 * Requires PHP:        8.0
 * Author:              DGW.ltd
 * Author URI:          https://dgw.ltd/
 * Text Domain:         dgwltd-pending-revisions
 * Domain Path:         /languages
 * Network:             false
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:          false
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DGW_PENDING_REVISIONS_VERSION', '1.0.0');
define('DGW_PENDING_REVISIONS_PLUGIN_FILE', __FILE__);
define('DGW_PENDING_REVISIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DGW_PENDING_REVISIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DGW_PENDING_REVISIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements check
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'DGW.ltd Pending Revisions requires PHP 8.0 or higher. Please update your PHP version.',
                'dgwltd-pending-revisions'
            )
        );
    });
    return;
}

if (version_compare($GLOBALS['wp_version'], '6.0', '<')) {
    add_action('admin_notices', function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'DGW.ltd Pending Revisions requires WordPress 6.0 or higher. Please update your WordPress installation.',
                'dgwltd-pending-revisions'
            )
        );
    });
    return;
}

// Composer autoloader
if (file_exists(DGW_PENDING_REVISIONS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once DGW_PENDING_REVISIONS_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Load required files manually if autoloader fails
 *
 * @since 1.0.0
 * @return void
 */
function load_required_files(): void {
    $required_files = [
        'includes/Core/Loader.php',
        'includes/Core/Plugin.php',
    ];
    
    foreach ($required_files as $file) {
        $file_path = DGW_PENDING_REVISIONS_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return void
 */
function init_plugin(): void {
    try {
        // Ensure required files are loaded
        if (!class_exists(__NAMESPACE__ . '\Core\Plugin')) {
            load_required_files();
        }
        
        // Initialize the plugin
        if (class_exists(__NAMESPACE__ . '\Core\Plugin')) {
            $plugin = Core\Plugin::get_instance();
            $plugin->run();
        } else {
            throw new \Exception('Plugin core class not found');
        }
    } catch (\Exception $e) {
        add_action('admin_notices', function () use ($e): void {
            printf(
                '<div class="notice notice-error"><p><strong>DGW Pending Revisions Error:</strong> %s</p></div>',
                esc_html($e->getMessage())
            );
        });
        error_log('DGW Pending Revisions initialization error: ' . $e->getMessage());
    }
}

// Initialize plugin on plugins_loaded hook
add_action('plugins_loaded', __NAMESPACE__ . '\init_plugin');

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 * @return void
 */
function activate_plugin(): void {
    // Ensure classes are loaded
    if (!class_exists(__NAMESPACE__ . '\Core\Activator')) {
        require_once DGW_PENDING_REVISIONS_PLUGIN_DIR . 'includes/Core/Activator.php';
    }
    
    try {
        // Create necessary database tables or options
        Core\Activator::activate();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    } catch (\Exception $e) {
        // Deactivate the plugin if activation fails
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html($e->getMessage()),
            esc_html__('Plugin Activation Error', 'dgwltd-pending-revisions'),
            ['back_link' => true]
        );
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activate_plugin');

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 * @return void
 */
function deactivate_plugin(): void {
    // Ensure classes are loaded
    if (!class_exists(__NAMESPACE__ . '\Core\Deactivator')) {
        require_once DGW_PENDING_REVISIONS_PLUGIN_DIR . 'includes/Core/Deactivator.php';
    }
    
    try {
        // Perform deactivation tasks
        Core\Deactivator::deactivate();
        
        // Clean up rewrite rules
        flush_rewrite_rules();
    } catch (\Exception $e) {
        error_log('DGW Pending Revisions deactivation error: ' . $e->getMessage());
    }
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate_plugin');

/**
 * Plugin uninstall hook
 * Note: This is handled in uninstall.php for security
 *
 * @since 1.0.0
 */