<?php
/**
 * Define the internationalization functionality.
 */
class Athena_i18n {
    /**
     * The domain specified for this plugin.
     *
     * @var string
     */
    private $domain;

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Set the domain equal to that of the specified domain.
     *
     * @param string $domain The domain that represents the locale of this plugin.
     */
    public function set_domain($domain) {
        $this->domain = $domain;
    }
}
