<?php
namespace AthenaAI\Core;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Handles automatic updates from GitHub using plugin-update-checker
 */
class UpdateChecker {
    /**
     * GitHub repository owner
     * @var string
     */
    private $owner;

    /**
     * GitHub repository name
     * @var string
     */
    private $repo;

    /**
     * GitHub Access Token for private repositories
     * @var string|null
     */
    private $access_token;

    /**
     * Initialize the update checker
     *
     * @param string $owner GitHub repository owner
     * @param string $repo GitHub repository name
     * @param string|null $access_token Optional GitHub access token for private repositories
     */
    public function __construct($owner, $repo, $access_token = null) {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->access_token = $access_token;
    }

    /**
     * Initialize the update checker functionality
     */
    public function init() {
        // Define the path to the update checker
        $updateCheckerPath =
            ATHENA_AI_PLUGIN_DIR .
            'includes/Libraries/plugin-update-checker/plugin-update-checker.php';

        // Check if update checker exists
        if (!file_exists($updateCheckerPath)) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>';
                echo esc_html__(
                    'Athena AI Plugin: Update checker library is missing. Please reinstall the plugin.',
                    'athena-ai'
                );
                echo '</p></div>';
            });
            return;
        }

        // Include the update checker
        require_once $updateCheckerPath;

        try {
            // Create the update checker instance
            $updateChecker = PucFactory::buildUpdateChecker(
                sprintf(
                    'https://github.com/%s/%s/',
                    urlencode($this->owner),
                    urlencode($this->repo)
                ),
                ATHENA_AI_PLUGIN_FILE,
                'athena-ai'
            );

            // Set to use releases instead of tags
            $updateChecker->getVcsApi()->enableReleaseAssets();

            // Set the branch that contains the stable release
            $updateChecker->setBranch('main');

            // If access token is provided, use it for authentication
            if ($this->access_token) {
                $updateChecker->setAuthentication($this->access_token);
            }

            // Add filters for update checking
            $pluginFile = plugin_basename(ATHENA_AI_PLUGIN_FILE);
            add_filter('pre_set_site_transient_update_plugins', function ($transient) use (
                $updateChecker,
                $pluginFile
            ) {
                if (!empty($transient) && isset($transient->response[$pluginFile])) {
                    $update = $transient->response[$pluginFile];
                    // Ensure the update ZIP is from a release
                    if (
                        isset($update->package) &&
                        is_string($update->package) &&
                        $update->package !== '' &&
                        strpos($update->package, '/releases/download/') === false
                    ) {
                        unset($transient->response[$pluginFile]);
                    }
                }
                return $transient;
            });
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="error"><p>';
                    echo esc_html(
                        sprintf(
                            __(
                                'Athena AI Plugin: Error initializing update checker: %s',
                                'athena-ai'
                            ),
                            $e->getMessage()
                        )
                    );
                    echo '</p></div>';
                });
            }
        }
    }

    /**
     * Get the update checker settings
     *
     * @return array Settings including owner and repo (but not access token for security)
     */
    public function get_settings() {
        return [
            'owner' => $this->owner,
            'repo' => $this->repo,
            'has_token' => !empty($this->access_token),
        ];
    }

    /**
     * Gibt die Statusmeldung für den Update Checker zurück
     *
     * @return string
     */
    public function get_status_message() {
        return sprintf(
            esc_html__(
                'Athena AI Update Checker Status: Connected to GitHub repository at https://github.com/%s/%s. Updates will be checked automatically.',
                'athena-ai'
            ),
            esc_html($this->owner),
            esc_html($this->repo)
        );
    }
}
