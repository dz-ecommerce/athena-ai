<?php
namespace AthenaAI\Admin;

class Admin extends BaseAdmin {
    /**
     * Render the overview page
     */
    public function render_overview_page() {
        $this->render_template('overview', [
            'title' => $this->__('Athena AI Overview', 'athena-ai'),
            'description' => $this->__('Welcome to Athena AI. Here you can manage your AI integration settings and view important information.', 'athena-ai'),
        ]);
    }
} 