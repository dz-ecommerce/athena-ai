<?php
namespace AthenaAI\Core;

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
        $updateCheckerPath = ATHENA_AI_PLUGIN_DIR . 'includes/Libraries/plugin-update-checker/plugin-update-checker.php';
        
        // Check if update checker exists
        if (!file_exists($updateCheckerPath)) {
            // Log error or notify admin that the library is missing
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo esc_html__('Athena AI Plugin: Update checker library is missing. Please reinstall the plugin.', 'athena-ai');
                echo '</p></div>';
            });
            return;
        }

        // Include the update checker
        require_once $updateCheckerPath;

        // Create the update checker instance
        $updateChecker = \Puc_v4_Factory::buildUpdateChecker(
            "https://github.com/{$this->owner}/{$this->repo}/",
            ATHENA_AI_PLUGIN_DIR . 'athena-ai.php',
            'athena-ai'
        );

        // Set the branch that contains the stable release
        $updateChecker->setBranch('main');

        // If access token is provided, use it for authentication
        if ($this->access_token) {
            $updateChecker->setAuthentication($this->access_token);
        }

        // Optional: Enable release assets support
        $updateChecker->getVcsApi()->enableReleaseAssets();
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
            'has_token' => !empty($this->access_token)
        ];
    }
}
