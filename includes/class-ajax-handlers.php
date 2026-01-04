<?php

namespace WP_AI_Assistant;

if( !defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax_Handlers {
    private $main;

    public function __construct( $main ) {
        $this->main = $main;
        $this->init();
    }

    public function init() {
        add_action( 'wp_ajax_wp_ai_assistant_test_connection', [ $this, 'test_connection_ajax' ] );
        add_action( 'wp_ajax_wp_ai_assistant_generate_thumbnail', [ $this, 'generate_thumbnail_ajax' ] );
        add_action( 'wp_ajax_wp_ai_assistant_generate_excerpt', [ $this, 'generate_excerpt_ajax' ] );
    }

    public function test_connection_ajax() {
        check_ajax_referer( 'wp_ai_assistant_test_connection', 'nonce' );

        if( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : 'chatgpt';

        if( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API Key is required' ] );
        }

        $result = false;
        $message = '';
        $error_message = '';

        if( $service === 'chatgpt' ) {
            $result = $this->test_chatgpt_api( $api_key, $error_message );
            if( $result ) {
                $message = 'Connection successful! API key is valid.';
            } else {
                $message = !empty( $error_message ) ? $error_message : 'Connection failed! Please check your API key.';
            }
        } elseif( $service === 'gemini' ) {
            $result = $this->test_gemini_api( $api_key, $error_message );
            if( $result ) {
                $message = 'Connection successful! API key is valid.';
            } else {
                $message = !empty( $error_message ) ? $error_message : 'Connection failed! Please check your API key.';
            }
        } elseif( $service === 'grok' ) {
            $result = $this->test_grok_api( $api_key, $error_message );
            if( $result ) {
                $message = 'Connection successful! API key is valid.';
            } else {
                $message = !empty( $error_message ) ? $error_message : 'Connection failed! Please check your API key.';
            }
        }

        if( $result ) {
            wp_send_json_success( [ 'message' => $message ] );
        } else {
            wp_send_json_error( [ 'message' => $message ] );
        }
    }

    public function test_chatgpt_api( $api_key, &$error_message ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hi'
                ]
            ],
            'max_tokens' => 5
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                } elseif( isset( $body['error']['code'] ) ) {
                    $error_message = 'Error code: ' . $body['error']['code'];
                } else {
                    $error_message = 'API returned error (Status: ' . $status_code . ')';
                }
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }
        
        return true;
    }

    public function test_gemini_api( $api_key, &$error_message ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . $api_key;
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'Hi'
                        ]
                    ]
                ]
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                } elseif( isset( $body['error']['status'] ) ) {
                    $error_message = 'Error: ' . $body['error']['status'];
                } else {
                    $error_message = 'API returned error (Status: ' . $status_code . ')';
                }
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }
        
        return true;
    }

    public function test_grok_api( $api_key, &$error_message ) {
        $url = 'https://api.x.ai/v1/chat/completions';
        
        $body = [
            'model' => 'grok-4-1-fast-non-reasoning',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hi'
                ]
            ],
            'max_tokens' => 5
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                    if( $status_code === 429 ) {
                        $error_message .= ' (Rate limit exceeded. Please try again later.)';
                    } elseif( $status_code === 422 ) {
                        $error_message .= ' (Invalid request format or parameters. Please check your API configuration.)';
                    }
                } elseif( isset( $body['error']['code'] ) ) {
                    $error_message = 'Error code: ' . $body['error']['code'];
                    if( isset( $body['error']['param'] ) ) {
                        $error_message .= ' - Parameter: ' . $body['error']['param'];
                    }
                    if( $status_code === 429 ) {
                        $error_message .= ' - Rate limit exceeded. Please try again later.';
                    } elseif( $status_code === 422 ) {
                        $error_message .= ' - Invalid request format or parameters.';
                    }
                } else {
                    if( $status_code === 429 ) {
                        $error_message = 'Rate limit exceeded (429). Too many requests. Please try again later.';
                    } elseif( $status_code === 422 ) {
                        $error_message = 'Unprocessable Entity (422). Invalid request format or parameters.';
                    } else {
                        $error_message = 'API returned error (Status: ' . $status_code . ')';
                    }
                }
            } else {
                if( $status_code === 429 ) {
                    $error_message = 'Rate limit exceeded (429). Too many requests. Please try again later.';
                } elseif( $status_code === 401 ) {
                    $error_message = 'Unauthorized (401). Please check your API key.';
                } elseif( $status_code === 404 ) {
                    $error_message = 'Not found (404). API endpoint or model may not be available. Please check the model name.';
                } elseif( $status_code === 422 ) {
                    $error_message = 'Unprocessable Entity (422). Invalid request format or parameters. Please check your API configuration.';
                } else {
                    $error_message = 'API request failed (Status: ' . $status_code . ')';
                }
            }
            return false;
        }
        
        return true;
    }

    public function generate_thumbnail_ajax() {
        check_ajax_referer( 'wp_ai_assistant_generate_thumbnail', 'nonce' );

        if( !current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if( !$post_id ) {
            wp_send_json_error( [ 'message' => 'Post ID is required' ] );
        }

        $post = get_post( $post_id );
        if( !$post ) {
            wp_send_json_error( [ 'message' => 'Post not found' ] );
        }

        $api_key = get_option( 'wp_ai_assistant_api_key', '' );
        $service = get_option( 'wp_ai_assistant_service', 'chatgpt' );

        if( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API Key is not configured' ] );
        }

        $content = !empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
        $content = wp_strip_all_tags( $content );
        $content = wp_trim_words( $content, 50 );

        if( empty( $content ) ) {
            wp_send_json_error( [ 'message' => 'Post content is empty' ] );
        }

        $site_context = get_option( 'wp_ai_assistant_site_context', '' );

        $prompt = 'Create a professional thumbnail image for a blog post. The post content is: ' . $content;
        if( !empty( $site_context ) ) {
            $prompt .= ' with the following style: ' . $site_context;
        }

        $seed = wp_rand( 0, 2147483647 );

        $result = false;
        $attachment_id = 0;
        $image_url = '';
        $error_message = '';

        if( $service === 'chatgpt' ) {
            $result = $this->generate_chatgpt_thumbnail( $api_key, $prompt, $post_id, $attachment_id, $image_url, $error_message, $seed );
        } elseif( $service === 'gemini' ) {
            $result = $this->generate_gemini_thumbnail( $api_key, $prompt, $post_id, $attachment_id, $image_url, $error_message, $seed );
        } elseif( $service === 'grok' ) {
            $result = $this->generate_grok_thumbnail( $api_key, $prompt, $post_id, $attachment_id, $image_url, $error_message, $seed );
        }

        if( $result && $attachment_id ) {
            set_post_thumbnail( $post_id, $attachment_id );
            wp_send_json_success( [
                'message' => 'Thumbnail generated successfully',
                'attachment_id' => $attachment_id,
                'image_url' => $image_url
            ] );
        } else {
            $message = !empty( $error_message ) ? $error_message : 'Failed to generate thumbnail';
            wp_send_json_error( [ 'message' => $message ] );
        }
    }

    public function generate_excerpt_ajax() {
        check_ajax_referer( 'wp_ai_assistant_generate_excerpt', 'nonce' );

        if( !current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if( !$post_id ) {
            wp_send_json_error( [ 'message' => 'Post ID is required' ] );
        }

        $post = get_post( $post_id );
        if( !$post ) {
            wp_send_json_error( [ 'message' => 'Post not found' ] );
        }

        $api_key = get_option( 'wp_ai_assistant_api_key', '' );
        $service = get_option( 'wp_ai_assistant_service', 'chatgpt' );

        if( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API Key is not configured' ] );
        }

        $content = $post->post_content;
        $content = wp_strip_all_tags( $content );
        $content = trim( $content );

        if( empty( $content ) || strlen( $content ) < 200 ) {
            wp_send_json_error( [ 'message' => 'Post content must be at least 200 characters long' ] );
        }

        $site_context = get_option( 'wp_ai_assistant_site_context', '' );

        $result = false;
        $excerpt = '';
        $error_message = '';

        if( $service === 'chatgpt' ) {
            $result = $this->generate_chatgpt_excerpt( $api_key, $content, $site_context, $excerpt, $error_message );
        } elseif( $service === 'gemini' ) {
            $result = $this->generate_gemini_excerpt( $api_key, $content, $site_context, $excerpt, $error_message );
        } elseif( $service === 'grok' ) {
            $result = $this->generate_grok_excerpt( $api_key, $content, $site_context, $excerpt, $error_message );
        }

        if( $result && !empty( $excerpt ) ) {
            wp_send_json_success( [
                'message' => 'Excerpt generated successfully',
                'excerpt' => $excerpt
            ] );
        } else {
            $message = !empty( $error_message ) ? $error_message : 'Failed to generate excerpt';
            wp_send_json_error( [ 'message' => $message ] );
        }
    }

    public function generate_chatgpt_excerpt( $api_key, $content, $site_context, &$excerpt, &$error_message ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $prompt = 'Create a SEO-friendly excerpt (120-160 characters) summarizing the following content. The excerpt should be concise, engaging, and include relevant keywords: ' . $content;
        if( !empty( $site_context ) ) {
            $prompt .= ' Context: ' . $site_context;
        }
        
        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) && isset( $body['error']['message'] ) ) {
                $error_message = $body['error']['message'];
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }

        if( !isset( $body['choices'][0]['message']['content'] ) ) {
            $error_message = 'Invalid response from API: Content not found';
            return false;
        }

        $excerpt = trim( $body['choices'][0]['message']['content'] );
        $excerpt = wp_strip_all_tags( $excerpt );
        
        if( strlen( $excerpt ) > 160 ) {
            $excerpt = mb_substr( $excerpt, 0, 157 ) . '...';
        }
        
        if( strlen( $excerpt ) < 120 ) {
            $excerpt = mb_substr( $excerpt, 0, 120 );
        }

        return !empty( $excerpt );
    }

    public function generate_gemini_excerpt( $api_key, $content, $site_context, &$excerpt, &$error_message ) {
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $api_key;
        
        $prompt = 'Create a SEO-friendly excerpt (120-160 characters) summarizing the following content. The excerpt should be concise, engaging, and include relevant keywords: ' . $content;
        if( !empty( $site_context ) ) {
            $prompt .= ' Context: ' . $site_context;
        }
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 100,
                'temperature' => 0.7
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) && isset( $body['error']['message'] ) ) {
                $error_message = $body['error']['message'];
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }

        if( !isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $error_message = 'Invalid response from API: Content not found';
            return false;
        }

        $excerpt = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
        $excerpt = wp_strip_all_tags( $excerpt );
        
        if( mb_strlen( $excerpt ) > 160 ) {
            $excerpt = mb_substr( $excerpt, 0, 157 ) . '...';
        }

        return !empty( $excerpt );
    }

    public function generate_grok_excerpt( $api_key, $content, $site_context, &$excerpt, &$error_message ) {
        $url = 'https://api.x.ai/v1/chat/completions';
        
        $prompt = 'Create a SEO-friendly excerpt (120-160 characters) summarizing the following content. The excerpt should be concise, engaging, and include relevant keywords: ' . $content;
        if( !empty( $site_context ) ) {
            $prompt .= ' Context: ' . $site_context;
        }
        
        $body = [
            'model' => 'grok-4-1-fast-non-reasoning',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 30,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) && isset( $body['error']['message'] ) ) {
                $error_message = $body['error']['message'];
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }

        if( !isset( $body['choices'][0]['message']['content'] ) ) {
            $error_message = 'Invalid response from API: Content not found';
            return false;
        }

        $excerpt = trim( $body['choices'][0]['message']['content'] );
        $excerpt = wp_strip_all_tags( $excerpt );
        
        if( strlen( $excerpt ) > 160 ) {
            $excerpt = mb_substr( $excerpt, 0, 157 ) . '...';
        }
        
        if( strlen( $excerpt ) < 120 ) {
            $excerpt = mb_substr( $excerpt, 0, 120 );
        }

        return !empty( $excerpt );
    }

    public function generate_chatgpt_thumbnail( $api_key, $prompt, $post_id, &$attachment_id, &$image_url, &$error_message, $seed = null ) {
        $image_size = $this->get_thumbnail_size();
        
        $url = 'https://api.openai.com/v1/images/generations';
        
        $body = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'size' => $image_size,
            'quality' => 'standard',
            'n' => 1
        ];

        if( $seed !== null ) {
            $body['seed'] = $seed;
        }

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 60,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                } elseif( isset( $body['error']['code'] ) ) {
                    $error_message = 'Error code: ' . $body['error']['code'];
                } else {
                    $error_message = 'API returned error (Status: ' . $status_code . ')';
                }
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }

        if( !isset( $body['data'][0]['url'] ) ) {
            $error_message = 'Invalid response from API: Image URL not found';
            return false;
        }

        $image_url = $body['data'][0]['url'];
        $attachment_id = $this->download_image_as_attachment( $image_url, $post_id, $prompt );

        if( $attachment_id <= 0 ) {
            $error_message = 'Failed to download and save image';
            return false;
        }

        return true;
    }

    public function generate_gemini_thumbnail( $api_key, $prompt, $post_id, &$attachment_id, &$image_url, &$error_message, $seed = null ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=' . $api_key;
        
        $generation_config = [
            'responseModalities' => ['IMAGE']
        ];

        if( $seed !== null ) {
            $generation_config['seed'] = $seed;
        }
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => $generation_config
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 60,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                } elseif( isset( $body['error']['code'] ) ) {
                    $error_message = 'Error code: ' . $body['error']['code'];
                } else {
                    $error_message = 'API returned error (Status: ' . $status_code . ')';
                }
            } else {
                $error_message = 'API request failed (Status: ' . $status_code . ')';
            }
            return false;
        }

        if( !isset( $body['candidates'][0]['content']['parts'][0]['inlineData'] ) ) {
            $error_message = 'Invalid response from API: Image data not found';
            if( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                $error_message .= '. Response: ' . $body['candidates'][0]['content']['parts'][0]['text'];
            }
            return false;
        }

        $inline_data = $body['candidates'][0]['content']['parts'][0]['inlineData'];
        
        if( !isset( $inline_data['data'] ) || !isset( $inline_data['mimeType'] ) ) {
            $error_message = 'Invalid image data format from API';
            return false;
        }

        $image_data = base64_decode( $inline_data['data'] );
        $upload_dir = wp_upload_dir();
        $filename = 'ai-thumbnail-' . $post_id . '-' . time() . '.png';
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if( file_put_contents( $file_path, $image_data ) === false ) {
            $error_message = 'Failed to save image file';
            return false;
        }
        
        $attachment_id = $this->create_attachment_from_file( $file_path, $post_id, $prompt );
        
        if( $attachment_id <= 0 ) {
            $error_message = 'Failed to create attachment';
            return false;
        }
        
        return true;
    }

    public function generate_grok_thumbnail( $api_key, $prompt, $post_id, &$attachment_id, &$image_url, &$error_message, $seed = null ) {
        $image_size = $this->get_thumbnail_size();
        
        $url = 'https://api.x.ai/v1/images/generations';
        
        $body = [
            'model' => 'grok-2-image',
            'prompt' => $prompt
        ];

        if( $seed !== null ) {
            $body['seed'] = $seed;
        }

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $body ),
            'timeout' => 60,
        ] );

        if( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );
        
        if( $status_code !== 200 ) {
            if( isset( $body['error'] ) ) {
                if( isset( $body['error']['message'] ) ) {
                    $error_message = $body['error']['message'];
                    if( $status_code === 422 ) {
                        $error_message .= ' (Invalid request format or parameters. Please check model name and request format.)';
                    } elseif( $status_code === 429 ) {
                        $error_message .= ' (Rate limit exceeded. Please try again later.)';
                    }
                } elseif( isset( $body['error']['code'] ) ) {
                    $error_message = 'Error code: ' . $body['error']['code'];
                    if( isset( $body['error']['param'] ) ) {
                        $error_message .= ' - Parameter: ' . $body['error']['param'];
                    }
                    if( $status_code === 422 ) {
                        $error_message .= ' - Invalid request format or parameters.';
                    } elseif( $status_code === 429 ) {
                        $error_message .= ' - Rate limit exceeded. Please try again later.';
                    }
                } else {
                    if( $status_code === 422 ) {
                        $error_message = 'Unprocessable Entity (422). Invalid request format or parameters. Please check model name and request format.';
                    } elseif( $status_code === 429 ) {
                        $error_message = 'Rate limit exceeded (429). Too many requests. Please try again later.';
                    } else {
                        $error_message = 'API returned error (Status: ' . $status_code . ')';
                    }
                }
            } else {
                if( $status_code === 422 ) {
                    $error_message = 'Unprocessable Entity (422). Invalid request format or parameters. Please check model name and request format.';
                } elseif( $status_code === 429 ) {
                    $error_message = 'Rate limit exceeded (429). Too many requests. Please try again later.';
                } elseif( $status_code === 401 ) {
                    $error_message = 'Unauthorized (401). Please check your API key.';
                } elseif( $status_code === 404 ) {
                    $error_message = 'Not found (404). API endpoint or model may not be available. Please check the model name.';
                } else {
                    $error_message = 'API request failed (Status: ' . $status_code . ')';
                }
            }
            return false;
        }

        if( !isset( $body['data'][0]['url'] ) ) {
            $error_message = 'Invalid response from API: Image URL not found';
            if( isset( $body['data'] ) && is_array( $body['data'] ) && count( $body['data'] ) > 0 ) {
                $error_message .= '. Response structure: ' . json_encode( $body['data'][0] );
            } elseif( isset( $body ) ) {
                $error_message .= '. Response: ' . json_encode( $body );
            }
            return false;
        }

        $image_url = $body['data'][0]['url'];
        $attachment_id = $this->download_image_as_attachment( $image_url, $post_id, $prompt );

        if( $attachment_id <= 0 ) {
            $error_message = 'Failed to download and save image';
            return false;
        }

        return true;
    }

    public function get_gemini_aspect_ratio( $size ) {
        $ratios = [
            '1024x1024' => '1:1',
            '1792x1024' => '16:9',
            '1024x1792' => '9:16',
        ];

        return isset( $ratios[$size] ) ? $ratios[$size] : '1:1';
    }

    public function create_attachment_from_file( $file_path, $post_id, $title ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $file_array = [
            'name' => basename( $file_path ),
            'tmp_name' => $file_path,
        ];

        $attachment_id = media_handle_sideload( $file_array, $post_id, $title );

        if( is_wp_error( $attachment_id ) ) {
            wp_delete_file( $file_path );
            return 0;
        }

        return $attachment_id;
    }

    public function get_thumbnail_size() {
        $registered_sizes = wp_get_registered_image_subsizes();
        
        if( isset( $registered_sizes['thumbnail'] ) ) {
            $w = $registered_sizes['thumbnail']['width'];
            $h = $registered_sizes['thumbnail']['height'];
            
            if( $h > 0 ) {
                $ratio = $w / $h;
                
                if( $ratio >= 1.5 ) {
                    return '1792x1024';
                } elseif( $ratio <= 0.6 ) {
                    return '1024x1792';
                }
            }
        }

        return '1024x1024';
    }

    public function download_image_as_attachment( $image_url, $post_id, $title ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $tmp = download_url( $image_url );

        if( is_wp_error( $tmp ) ) {
            return 0;
        }

        $file_array = [
            'name' => sanitize_file_name( 'ai-thumbnail-' . $post_id . '.png' ),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload( $file_array, $post_id, $title );

        if( is_wp_error( $attachment_id ) ) {
            wp_delete_file( $file_array['tmp_name'] );
            return 0;
        }

        return $attachment_id;
    }
}

