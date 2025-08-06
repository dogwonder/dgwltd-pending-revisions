<?php
/**
 * Main Plugin Class
 *
 * @package DGW\PendingRevisions\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Core;

/**
 * Main Plugin Class
 *
 * Singleton pattern implementation for the main plugin functionality.
 * Coordinates all plugin components and handles initialization.
 *
 * @since 1.0.0
 */
final class Plugin {
    
    /**
     * Plugin instance
     *
     * @since 1.0.0
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;
    
    /**
     * Plugin loader instance
     *
     * @since 1.0.0
     * @var Loader|null
     */
    private ?Loader $loader = null;
    
    /**
     * Plugin version
     *
     * @since 1.0.0
     * @var string
     */
    private string $version;
    
    /**
     * Plugin text domain
     *
     * @since 1.0.0
     * @var string
     */
    private string $text_domain;
    
    /**
     * Private constructor to prevent multiple instances
     *
     * @since 1.0.0
     * @throws \Exception If initialization fails
     */
    private function __construct() {
        $this->version = DGW_PENDING_REVISIONS_VERSION ?? '1.0.0';
        $this->text_domain = 'dgwltd-pending-revisions';
        
        try {
            // Load the Loader class first
            $this->load_loader();
            $this->loader = new Loader();
            
            $this->load_dependencies();
            $this->set_locale();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $this->define_api_hooks();
        } catch (\Exception $e) {
            error_log('DGW Pending Revisions Plugin initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get plugin instance (Singleton pattern)
     *
     * @since 1.0.0
     * @return Plugin
     * @throws \Exception If plugin initialization fails
     */
    public static function get_instance(): Plugin {
        if (null === self::$instance) {
            try {
                self::$instance = new self();
            } catch (\Exception $e) {
                error_log('Failed to initialize DGW Pending Revisions Plugin: ' . $e->getMessage());
                throw new \Exception('Plugin initialization failed: ' . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Load the Loader class
     *
     * @since 1.0.0
     * @return void
     */
    private function load_loader(): void {
        if (!class_exists(__NAMESPACE__ . '\Loader')) {
            require_once DGW_PENDING_REVISIONS_PLUGIN_DIR . 'includes/Core/Loader.php';
        }
    }
    
    /**
     * Load plugin dependencies
     *
     * @since 1.0.0
     * @return void
     * @throws \Exception If required file is missing
     */
    private function load_dependencies(): void {
        $required_files = [
            // Core classes (Loader already loaded)
            'includes/Core/I18n.php',
            'includes/Core/Activator.php',
            'includes/Core/Deactivator.php',
            
            // Admin classes
            'includes/Admin/Admin.php',
            'includes/Admin/Assets.php',
            'includes/Admin/Dashboard.php',
            'includes/Admin/Meta_Boxes.php',
            'includes/Admin/Post_Save_Handler.php',
            
            // API classes
            'includes/API/REST_Controller.php',
            'includes/API/Revisions_Controller.php',
            
            // Database classes
            'includes/Database/Repository.php',
            'includes/Database/Revisions_Repository.php',
            
            // Frontend classes
            'includes/Frontend/Public_Hooks.php',
            'includes/Frontend/Content_Filter.php',
            
            // Utility classes
            'includes/Utils/Capabilities.php',
            'includes/Utils/Helpers.php',
        ];
        
        foreach ($required_files as $file) {
            $file_path = DGW_PENDING_REVISIONS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new \Exception("Required file not found: {$file}");
            }
        }
    }
    
    /**
     * Set plugin locale for internationalization
     *
     * @since 1.0.0
     * @return void
     */
    private function set_locale(): void {
        $plugin_i18n = new I18n();
        $plugin_i18n->set_domain($this->text_domain);
        
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Define admin-specific hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function define_admin_hooks(): void {
        $plugin_admin = new \DGW\PendingRevisions\Admin\Admin($this->get_version(), $this->get_text_domain());
        $admin_assets = new \DGW\PendingRevisions\Admin\Assets($this->get_version());
        $admin_dashboard = new \DGW\PendingRevisions\Admin\Dashboard();
        $admin_meta_boxes = new \DGW\PendingRevisions\Admin\Meta_Boxes();
        $post_save_handler = new \DGW\PendingRevisions\Admin\Post_Save_Handler();
        
        // Admin initialization
        $plugin_admin->init();
        $this->loader->add_action('admin_enqueue_scripts', $admin_assets, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin_assets, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin_dashboard, 'add_admin_menu');
        $this->loader->add_action('add_meta_boxes', $admin_meta_boxes, 'add_meta_boxes');
        $this->loader->add_action('save_post', $admin_meta_boxes, 'save_meta_boxes');
        
        // Post save handling for pending mode
        $post_save_handler->init();
        
        // Block Editor integration
        $this->loader->add_action('enqueue_block_editor_assets', $admin_assets, 'enqueue_block_editor_assets');
        $this->loader->add_action('init', $this, 'register_block_editor_plugins');
    }
    
    /**
     * Define public-facing hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function define_public_hooks(): void {
        $plugin_public = new \DGW\PendingRevisions\Frontend\Public_Hooks($this->get_version(), $this->get_text_domain());
        $content_filter = new \DGW\PendingRevisions\Frontend\Content_Filter();
        
        // Public hooks
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Content filtering
        $this->loader->add_filter('the_content', $content_filter, 'filter_content', -1);
        $this->loader->add_filter('the_title', $content_filter, 'filter_title', -1, 2);
        $this->loader->add_filter('the_excerpt', $content_filter, 'filter_excerpt', -1);
        $this->loader->add_filter('get_post_metadata', $content_filter, 'filter_meta', -1, 4);
    }
    
    /**
     * Define API hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function define_api_hooks(): void {
        $revisions_controller = new \DGW\PendingRevisions\API\Revisions_Controller();
        
        // REST API initialization - Direct hook to bypass potential Loader issues
        add_action('rest_api_init', [$revisions_controller, 'register_routes']);
        
        // Capabilities setup
        $capabilities = new \DGW\PendingRevisions\Utils\Capabilities();
        add_action('init', [$capabilities, 'add_capabilities']);
    }
    
    /**
     * Register Block Editor plugins
     *
     * @since 1.0.0
     * @return void
     */
    public function register_block_editor_plugins(): void {
        // Register the sidebar plugin script
        wp_register_script(
            'dgwltd-pending-revisions-sidebar',
            DGW_PENDING_REVISIONS_PLUGIN_URL . 'build/sidebar.js',
            [
                'wp-blocks',
                'wp-edit-post',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-plugins',
                'wp-compose',
                'wp-i18n'
            ],
            $this->version,
            true
        );
        
        // Localize script with plugin data
        wp_localize_script(
            'dgwltd-pending-revisions-sidebar',
            'dgwPendingRevisionsData',
            [
                'apiUrl' => rest_url('dgw-pending-revisions/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => get_current_user_id(),
                'capabilities' => [
                    'accept_revisions' => current_user_can('accept_revisions'),
                    'edit_posts' => current_user_can('edit_posts'),
                ],
                'textDomain' => $this->text_domain,
            ]
        );
    }
    
    /**
     * Run the plugin loader
     *
     * @since 1.0.0
     * @return void
     */
    public function run(): void {
        $this->loader->run();
    }
    
    /**
     * Get plugin version
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }
    
    /**
     * Get plugin text domain
     *
     * @since 1.0.0
     * @return string
     */
    public function get_text_domain(): string {
        return $this->text_domain;
    }
    
    /**
     * Get plugin loader
     *
     * @since 1.0.0
     * @return Loader
     */
    public function get_loader(): Loader {
        return $this->loader;
    }
    
    /**
     * Prevent cloning
     *
     * @since 1.0.0
     * @return void
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     *
     * @since 1.0.0
     * @return void
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}