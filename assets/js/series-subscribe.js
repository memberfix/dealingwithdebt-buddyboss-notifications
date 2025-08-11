jQuery(document).ready(function($) {
    'use strict';

    // Handle series subscribe/unsubscribe button clicks
    $(document).on('click', '.series-subscribe-toggle', function(e) {
        e.preventDefault();
        
        let $button = $(this);
        let $wrapper = $button.closest('.series-subscribe-wrapper');
        let $message = $wrapper.find('.series-subscribe-message');
        let $buttonText = $button.find('.button-text');
        let $loadingText = $button.find('.loading-text');
        let $subscriptionStatus = $wrapper.find('.subscription-status');
        let $subscriberCount = $wrapper.find('.subscriber-count');
        
        let seriesId = $wrapper.data('series-id');
        
        // Show loading state
        $button.prop('disabled', true);
        $buttonText.hide();
        $loadingText.show();
        $message.hide();
        
        // AJAX request to toggle subscription
        $.ajax({
            url: series_subscribe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'series_subscribe_toggle',
                nonce: series_subscribe_ajax.nonce,
                series_id: seriesId
            },
            success: function(response) {
                if (response.success) {
                    // Update button state and text
                    let newState = response.data.action === 'subscribed' ? 'subscribed' : 'unsubscribed';
                    $button.data('state', newState);
                    $buttonText.text(response.data.button_text);
                    
                    // Update subscription status message
                    if (newState === 'subscribed') {
                        $subscriptionStatus
                            .removeClass('unsubscribed')
                            .addClass('subscribed')
                            .html('✓ ' + series_subscribe_ajax.subscribed_message);
                        $button.removeClass('unsubscribed').addClass('subscribed');
                    } else {
                        $subscriptionStatus
                            .removeClass('subscribed')
                            .addClass('unsubscribed')
                            .text(series_subscribe_ajax.unsubscribed_message);
                        $button.removeClass('subscribed').addClass('unsubscribed');
                    }
                    
                    // Update subscriber count if visible
                    if ($subscriberCount.length) {
                        let currentCount = parseInt($subscriberCount.text().match(/\d+/)[0]) || 0;
                        let newCount = newState === 'subscribed' ? currentCount + 1 : currentCount - 1;
                        let countText = newCount === 1 ? 
                            newCount + ' ' + series_subscribe_ajax.subscriber_singular :
                            newCount + ' ' + series_subscribe_ajax.subscriber_plural;
                        $subscriberCount.text(countText);
                    }
                    
                    // Show success message
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.data.message)
                        .fadeIn()
                        .delay(3000)
                        .fadeOut();
                        
                } else {
                    // Show error message
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message || series_subscribe_ajax.error_message)
                        .fadeIn();
                }
            },
            error: function(xhr, status, error) {
                // Show generic error message
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text(series_subscribe_ajax.error_message)
                    .fadeIn();
                    
                console.error('Series Subscribe AJAX Error:', error);
            },
            complete: function() {
                // Hide loading state
                $button.prop('disabled', false);
                $loadingText.hide();
                $buttonText.show();
            }
        });
    });
    
    // Handle login button clicks (track for analytics if needed)
    $(document).on('click', '.series-login-btn', function(e) {
        // You can add analytics tracking here
        console.log('Series subscription login button clicked');
    });
    
    // Auto-hide messages after some time
    setTimeout(function() {
        $('.series-subscribe-message.success').fadeOut();
    }, 5000);
    
    // Accessibility improvements
    $('.series-subscribe-toggle').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space key
            e.preventDefault();
            $(this).click();
        }
    });
    
    // Add ARIA attributes for better accessibility
    $('.series-subscribe-toggle').attr({
        'role': 'button',
        'aria-pressed': function() {
            return $(this).data('state') === 'subscribed' ? 'true' : 'false';
        }
    });
});
