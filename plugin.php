<?php

/*
Plugin Name: Blogger AI Assistant
Description: Use your favourite AI model for writing in WordPress
Version: 1.0.0
Author: Mangabooth
Author URI: https://mangabooth.com
License: GPL-2.0+
*/

namespace WP_AI_Assistant;

if ( ! defined( 'ABSPATH' ) ) exit;

if( !defined( 'WP_AI_ASSISTANT_DIR' ) ){
    define( 'WP_AI_ASSISTANT_DIR', plugin_dir_path( __FILE__ ) );
}

if( !defined( 'WP_AI_ASSISTANT_URL' ) ){
    define( 'WP_AI_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );
}

if( !defined( 'WP_AI_ASSISTANT_VERSION' ) ){
    define( 'WP_AI_ASSISTANT_VERSION', '1.0.0' );
}

if( !defined( 'WP_AI_ASSISTANT_TEXTDOMAIN' ) ){
    define( 'WP_AI_ASSISTANT_TEXTDOMAIN', 'wp-ai-assistant' );
}

class WP_AI_Assistant {
    private $ajax_handlers;
    private $admin_settings;

    public function __construct() {
        $this->load_dependencies();
        $this->init();
    }

    private function load_dependencies() {
        require_once WP_AI_ASSISTANT_DIR . 'includes/class-ajax-handlers.php';
        require_once WP_AI_ASSISTANT_DIR . 'includes/class-admin-settings.php';
    }

    public function init() {
        $this->ajax_handlers = new Ajax_Handlers( $this );
        $this->admin_settings = new Admin_Settings( $this );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function enqueue_admin_scripts( $hook ) {
        wp_enqueue_style(
            'wp-ai-assistant-admin',
            WP_AI_ASSISTANT_URL . 'assets/css/admin.css',
            [],
            WP_AI_ASSISTANT_VERSION
        );

        wp_enqueue_script(
            'wp-ai-assistant-admin',
            WP_AI_ASSISTANT_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            WP_AI_ASSISTANT_VERSION,
            true
        );

        $this->localize_scripts( $hook );
    }

    private function localize_scripts( $hook ) {
        $admin_settings = $this->admin_settings;
        $ai_services = $admin_settings->get_ai_services();
        $service_descriptions = [];

        foreach( $ai_services as $value => $label ) {
            $service_descriptions[$value] = $admin_settings->get_service_description( $value );
        }

        $localize_data = [
            'settings' => [
                'serviceDescriptions' => $service_descriptions,
                'testConnectionNonce' => wp_create_nonce( 'wp_ai_assistant_test_connection' ),
            ],
            'postEditor' => [
                'postId' => 0,
                'generateThumbnailNonce' => wp_create_nonce( 'wp_ai_assistant_generate_thumbnail' ),
                'generateExcerptNonce' => wp_create_nonce( 'wp_ai_assistant_generate_excerpt' ),
            ],
        ];

        if( in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
            global $post;
            if( $post ) {
                $localize_data['postEditor']['postId'] = $post->ID;
            }
        }

        wp_localize_script( 'wp-ai-assistant-admin', 'wpAiAssistant', $localize_data );
    }
}

new WP_AI_Assistant();
