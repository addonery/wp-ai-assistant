<?php

namespace WP_AI_Assistant;

if( !defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Settings {
    private $main;

    public function __construct( $main ) {
        $this->main = $main;
        $this->init();
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'save_settings' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP AI Assistant',
            'WP AI Assistant',
            'manage_options',
            'wp-ai-assistant',
            [ $this, 'render_admin_page' ]
        );
    }

    public function save_settings() {
        if( !isset( $_POST['wp_ai_assistant_settings_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['wp_ai_assistant_settings_nonce'] ) );
        if( !wp_verify_nonce( $nonce, 'wp_ai_assistant_save_settings' ) ) {
            return;
        }

        if( !current_user_can( 'manage_options' ) ) {
            return;
        }

        if( isset( $_POST['wp_ai_assistant_api_key'] ) ) {
            update_option( 'wp_ai_assistant_api_key', sanitize_text_field( wp_unslash( $_POST['wp_ai_assistant_api_key'] ) ) );
        }

        if( isset( $_POST['wp_ai_assistant_service'] ) ) {
            update_option( 'wp_ai_assistant_service', sanitize_text_field( wp_unslash( $_POST['wp_ai_assistant_service'] ) ) );
        }

        if( isset( $_POST['wp_ai_assistant_site_context'] ) ) {
            update_option( 'wp_ai_assistant_site_context', sanitize_textarea_field( wp_unslash( $_POST['wp_ai_assistant_site_context'] ) ) );
        }

        add_settings_error( 'wp_ai_assistant_settings', 'settings_saved', 'Settings saved successfully.', 'updated' );
    }

    public function get_ai_services() {
        return [
            'chatgpt' => 'ChatGPT',
            'gemini' => 'Gemini',
            'grok' => 'Grok',
        ];
    }

    public function get_service_description( $service ) {
        $descriptions = [
            'chatgpt' => 'To get your ChatGPT API key, visit <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a> and create a new secret key. Copy the key and paste it above.',
            'gemini' => 'To get your Gemini API key, visit <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a> and create a new API key. Copy the key and paste it above.',
            'grok' => 'To get your Grok API key, visit <a href="https://console.x.ai" target="_blank">xAI Console</a> and create a new API key. Copy the key and paste it above.',
        ];

        return isset( $descriptions[$service] ) ? $descriptions[$service] : '';
    }

    public function render_admin_page() {
        $api_key = get_option( 'wp_ai_assistant_api_key', '' );
        $selected_service = get_option( 'wp_ai_assistant_service', 'chatgpt' );
        $site_context = get_option( 'wp_ai_assistant_site_context', '' );
        $ai_services = $this->get_ai_services();

        settings_errors( 'wp_ai_assistant_settings' );
        ?>
        <div class="wrap">
            <h1>WP AI Assistant Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'wp_ai_assistant_save_settings', 'wp_ai_assistant_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wp_ai_assistant_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" id="wp_ai_assistant_api_key" name="wp_ai_assistant_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                            <p class="description">Enter your AI service API key</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wp_ai_assistant_service">AI Services</label>
                        </th>
                        <td>
                            <select id="wp_ai_assistant_service" name="wp_ai_assistant_service" class="regular-text">
                                <?php foreach( $ai_services as $value => $label ): ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_service, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select your AI service</p>
                            <p class="description" id="wp_ai_assistant_service_description" style="margin-top: 10px;">
                                <?php echo wp_kses_post( $this->get_service_description( $selected_service ) ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wp_ai_assistant_site_context">Site Context</label>
                        </th>
                        <td>
                            <textarea id="wp_ai_assistant_site_context" name="wp_ai_assistant_site_context" rows="5" class="large-text"><?php echo esc_textarea( $site_context ); ?></textarea>
                            <p class="description">Enter keywords or context about your site to help generate more accurate thumbnails (e.g., "manga, anime, Japanese comics, action, fantasy")</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>Test Connection</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Connection Test</th>
                    <td>
                        <button type="button" id="wp_ai_assistant_test_btn" class="button">Test API Key</button>
                        <span id="wp_ai_assistant_test_result" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}

