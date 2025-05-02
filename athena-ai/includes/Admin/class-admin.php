<?php
/**
 * Admin class for the Athena AI plugin
 *
 * @package AthenaAI
 * @subpackage Admin
 */

namespace AthenaAI\Admin;

/**
 * Admin class
 */
class Admin extends BaseAdmin {
    /**
     * Render the overview page
     *
     * @return void
     */
    public function render_overview_page() {
        $this->render_template('overview', [
            'title' => $this->__('Athena AI Overview', 'athena-ai'),
            'description' => $this->__(
                'Welcome to Athena AI. Here you can manage your AI integration settings and view important information.',
                'athena-ai'
            ),
        ]);
    }
}
