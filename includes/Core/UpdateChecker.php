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
        // Check if plugin-update-checker is already loaded
        if (!class_exists('Puc_v4_Factory')) {
            require_once ATHENA_AI_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
        }

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
