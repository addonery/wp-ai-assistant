jQuery(document).ready(function($) {
    // Settings page
    if( $('#wp_ai_assistant_service').length ) {
        var serviceDescriptions = wpAiAssistant.settings.serviceDescriptions || {};

        $('#wp_ai_assistant_service').on('change', function() {
            var selectedService = $(this).val();
            var description = serviceDescriptions[selectedService] || '';
            $('#wp_ai_assistant_service_description').html(description);
        });

        $('#wp_ai_assistant_test_btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#wp_ai_assistant_test_result');
            var apiKey = $('#wp_ai_assistant_api_key').val();
            var service = $('#wp_ai_assistant_service').val();

            if( !apiKey ) {
                $result.html('<span style="color: red;">Please enter API Key first</span>');
                return;
            }

            $btn.prop('disabled', true).text('Testing...');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_ai_assistant_test_connection',
                    nonce: wpAiAssistant.settings.testConnectionNonce,
                    api_key: apiKey,
                    service: service
                },
                success: function(response) {
                    if( response.success ) {
                        $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span style="color: red;">✗ An error occurred. Please try again.</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test API Key');
                }
            });
        });
    }

    // Featured image button
    setTimeout(function() {
        function addGenerateAIButtons() {
            var $featuredImageBox = $('.editor-post-featured-image');
            if( $featuredImageBox.length && !$featuredImageBox.find('#wp_ai_assistant_generate_thumbnail').length ) {
                var $buttonContainer = $('<p style="margin-top: 10px;"></p>');
                var $button = $('<a href="#" id="wp_ai_assistant_generate_thumbnail" class="button">Generate Thumbnail using AI</a>');
                $buttonContainer.append($button);
                $featuredImageBox.append($buttonContainer);
            }

            var $featuredExcerpt = $('.editor-post-excerpt__dropdown');
            if( $featuredExcerpt.length && !$featuredExcerpt.find('#wp_ai_assistant_generate_excerpt').length ) {
                var $buttonContainer = $('<p style="margin-top: 10px;"></p>');
                var $button = $('<a href="#" id="wp_ai_assistant_generate_excerpt" class="button">Generate Excerpt using AI</a>');
                $buttonContainer.append($button);
                $featuredExcerpt.append($buttonContainer);
            }
        }

        addGenerateAIButtons();
        
        var observer = new MutationObserver(function(mutations) {
            addGenerateAIButtons();
        });
        
        var targetNode = document.getElementsByClassName('editor-post-featured-image')[0];
        if( targetNode ) {
            observer.observe(targetNode, { childList: true, subtree: true });
        }
        
        var excerptNode = document.getElementsByClassName('editor-post-excerpt__dropdown')[0];
        if( excerptNode ) {
            observer.observe(excerptNode, { childList: true, subtree: true });
        }

        function getPostId() {
            if( typeof wp !== 'undefined' && wp.data && wp.data.select ) {
                var postId = wp.data.select('core/editor').getCurrentPostId();
                if( postId ) {
                    return postId;
                }
            }
            return wpAiAssistant.postEditor.postId || 0;
        }

        $(document).on('click', '#wp_ai_assistant_generate_thumbnail', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = getPostId();
            
            if( !postId ) {
                alert('Post ID not found');
                return;
            }

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_ai_assistant_generate_thumbnail',
                    nonce: wpAiAssistant.postEditor.generateThumbnailNonce,
                    post_id: postId
                },
                success: function(response) {
                    if( response.success ) {
                        if( response.data && response.data.attachment_id ) {
                            if( typeof wp !== 'undefined' && wp.data && wp.data.dispatch ) {
                                wp.data.dispatch('core/editor').editPost({
                                    featured_media: response.data.attachment_id
                                });
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            } else if( typeof wp !== 'undefined' && wp.media && wp.media.featuredImage ) {
                                wp.media.featuredImage.set(response.data.attachment_id);
                            } else {
                                location.reload();
                            }
                        } else {
                            alert('Error: Invalid response from server. Attachment ID not found.');
                            $btn.prop('disabled', false).text('Generate Thumbnail using AI');
                        }
                    } else {
                        alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to generate thumbnail'));
                        $btn.prop('disabled', false).text('Generate Thumbnail using AI');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $btn.prop('disabled', false).text('Generate Thumbnail using AI');
                }
            });
        });

        $(document).on('click', '#wp_ai_assistant_generate_excerpt', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = getPostId();
            
            if( !postId ) {
                alert('Post ID not found');
                return;
            }

            function getPostContent() {
                if( typeof wp !== 'undefined' && wp.data && wp.data.select ) {
                    var content = wp.data.select('core/editor').getEditedPostAttribute('content');
                    if( content ) {
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = content;
                        return tempDiv.textContent || tempDiv.innerText || '';
                    }
                }
                return '';
            }

            var postContent = getPostContent();
            
            if( !postContent || postContent.trim().length < 200 ) {
                alert('Post content must be at least 200 characters long to generate excerpt.');
                return;
            }

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_ai_assistant_generate_excerpt',
                    nonce: wpAiAssistant.postEditor.generateExcerptNonce,
                    post_id: postId
                },
                success: function(response) {
                    if( response.success ) {
                        if( response.data && response.data.excerpt ) {
                            if( typeof wp !== 'undefined' && wp.data && wp.data.dispatch ) {
                                wp.data.dispatch('core/editor').editPost({
                                    excerpt: response.data.excerpt
                                });
                                $btn.prop('disabled', false).text('Generate Excerpt using AI');
                            } else {
                                var $excerptField = $('#excerpt');
                                if( $excerptField.length ) {
                                    $excerptField.val(response.data.excerpt);
                                }
                                $btn.prop('disabled', false).text('Generate Excerpt using AI');
                            }
                        } else {
                            alert('Error: Invalid response from server. Excerpt not found.');
                            $btn.prop('disabled', false).text('Generate Excerpt using AI');
                        }
                    } else {
                        alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to generate excerpt'));
                        $btn.prop('disabled', false).text('Generate Excerpt using AI');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $btn.prop('disabled', false).text('Generate Excerpt using AI');
                }
            });
        });
    }, 1000);
});

