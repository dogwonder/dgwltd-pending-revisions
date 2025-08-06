<?php
/**
 * Internationalization (i18n) Handler
 *
 * @package DGW\PendingRevisions\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Core;

/**
 * Internationalization (i18n) Handler
 *
 * Handles loading of plugin text domain for internationalization support.
 *
 * @since 1.0.0
 */
class I18n {
    
    /**
     * Plugin text domain
     *
     * @since 1.0.0
     * @var string
     */
    private string $domain = 'dgwltd-pending-revisions';
    
    /**
     * Set the text domain
     *
     * @since 1.0.0
     * @param string $domain The text domain to use
     * @return void
     */
    public function set_domain(string $domain): void {
        $this->domain = $domain;
    }
    
    /**
     * Load the plugin text domain for translation
     *
     * @since 1.0.0
     * @return void
     */
    public function load_plugin_textdomain(): void {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(DGW_PENDING_REVISIONS_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Get the current text domain
     *
     * @since 1.0.0
     * @return string
     */
    public function get_domain(): string {
        return $this->domain;
    }
}