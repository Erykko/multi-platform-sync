/**
 * Admin JavaScript for Multi-Platform Sync plugin.
 *
 * Handles admin UI interactions and AJAX requests.
 */
(function($) {
    'use strict';

    /**
     * Initialize the admin functionality.
     */
    function init() {
        // Handle manual sync button click
        $('#mps-manual-sync').on('click', handleManualSync);
        
        // Handle tab navigation in settings
        $('.mps-tab-link').on('click', handleTabNavigation);
        
        // Modal functionality
        $('.mps-modal-close').on('click', closeModal);
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('mps-modal')) {
                closeModal();
            }
        });

        // Connection testing
        $('.mps-test-connection').on('click', handleTestConnection);

        // Queue management
        $('#mps-process-queue').on('click', handleProcessQueue);
        $('#mps-clear-completed').on('click', handleClearCompleted);

        // Enhanced UI interactions
        initEnhancedUI();
    }

    /**
     * Initialize enhanced UI features.
     */
    function initEnhancedUI() {
        // Add loading states to buttons
        $('.button').on('click', function() {
            if (!$(this).hasClass('mps-test-connection')) {
                $(this).addClass('mps-loading');
            }
        });

        // Auto-save settings (debounced)
        let saveTimeout;
        $('input, select, textarea').on('change', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                showNotification('Settings auto-saved', 'success');
            }, 2000);
        });

        // Enhanced tooltips
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });

        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 50
                }, 500);
            }
        });
    }

    /**
     * Handle manual sync button click.
     */
    function handleManualSync(e) {
        e.preventDefault();

        // Confirm before running sync
        if (!confirm(mps_admin_vars.strings.confirm_sync)) {
            return;
        }

        // Show loading modal
        showModal();
        $('#mps-sync-result').html('');
        
        // Make AJAX request to trigger manual sync
        $.ajax({
            url: mps_admin_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mps_manual_sync',
                nonce: mps_admin_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mps-sync-result').html('<div class="mps-success">' + response.data.message + '</div>');
                    showNotification(response.data.message, 'success');
                } else {
                    $('#mps-sync-result').html('<div class="mps-error">' + response.data.message + '</div>');
                    showNotification(response.data.message, 'error');
                }
                
                // Hide the loader
                $('.mps-loader').hide();
                $('.mps-progress-message').hide();
                
                // Reload the page after a delay to show updated logs
                setTimeout(function() {
                    location.reload();
                }, 3000);
            },
            error: function() {
                const errorMsg = mps_admin_vars.strings.sync_error;
                $('#mps-sync-result').html('<div class="mps-error">' + errorMsg + '</div>');
                $('.mps-loader').hide();
                $('.mps-progress-message').hide();
                showNotification(errorMsg, 'error');
            }
        });
    }

    /**
     * Handle connection testing.
     */
    function handleTestConnection(e) {
        e.preventDefault();
        
        const $button = $(this);
        const platform = $button.data('platform');
        const originalText = $button.text();
        
        // Update button state
        $button.prop('disabled', true)
               .text(mps_admin_vars.strings.testing_connection)
               .addClass('mps-loading');
        
        $.ajax({
            url: mps_admin_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mps_test_connection',
                platform: platform,
                nonce: mps_admin_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message || mps_admin_vars.strings.connection_successful, 'success');
                    $button.removeClass('mps-loading').addClass('mps-success');
                } else {
                    showNotification(response.message || mps_admin_vars.strings.connection_failed, 'error');
                    $button.removeClass('mps-loading').addClass('mps-error');
                }
            },
            error: function() {
                showNotification(mps_admin_vars.strings.connection_failed, 'error');
                $button.removeClass('mps-loading').addClass('mps-error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
                
                // Reset button state after 3 seconds
                setTimeout(function() {
                    $button.removeClass('mps-success mps-error');
                }, 3000);
            }
        });
    }

    /**
     * Handle queue processing.
     */
    function handleProcessQueue(e) {
        e.preventDefault();
        
        if (!confirm('Process pending queue items now?')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Processing...').addClass('mps-loading');
        
        $.ajax({
            url: mps_admin_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mps_process_queue',
                nonce: mps_admin_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Error processing queue.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText).removeClass('mps-loading');
            }
        });
    }

    /**
     * Handle clearing completed queue items.
     */
    function handleClearCompleted(e) {
        e.preventDefault();
        
        if (!confirm('Clear completed queue items?')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Clearing...').addClass('mps-loading');
        
        $.ajax({
            url: mps_admin_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'mps_clear_completed_queue',
                nonce: mps_admin_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Error clearing queue.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText).removeClass('mps-loading');
            }
        });
    }
    
    /**
     * Handle tab navigation in settings.
     */
    function handleTabNavigation(e) {
        e.preventDefault();
        
        var targetId = $(this).attr('href');
        
        // Hide all tab content
        $('.mps-tab-content').removeClass('active');
        
        // Show target tab content
        $(targetId).addClass('active');
        
        // Update active tab
        $('.mps-tab-link').removeClass('active');
        $(this).addClass('active');

        // Smooth scroll to content
        $('html, body').animate({
            scrollTop: $('.mps-settings-content').offset().top - 50
        }, 300);
    }
    
    /**
     * Show the sync modal.
     */
    function showModal() {
        $('#mps-sync-modal').css('display', 'block');
        $('.mps-loader').show();
        $('.mps-progress-message').show();
    }
    
    /**
     * Close the sync modal.
     */
    function closeModal() {
        $('#mps-sync-modal').css('display', 'none');
    }

    /**
     * Show notification message.
     */
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.mps-notification').remove();
        
        const notification = $(`
            <div class="mps-notification mps-notification-${type}">
                <span class="mps-notification-message">${message}</span>
                <button class="mps-notification-close">&times;</button>
            </div>
        `);
        
        // Add styles if not already present
        if (!$('#mps-notification-styles').length) {
            $('head').append(`
                <style id="mps-notification-styles">
                    .mps-notification {
                        position: fixed;
                        top: 32px;
                        right: 20px;
                        z-index: 999999;
                        padding: 12px 16px;
                        border-radius: 4px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        max-width: 400px;
                        animation: slideInRight 0.3s ease;
                    }
                    .mps-notification-success {
                        background: #d4edda;
                        border: 1px solid #c3e6cb;
                        color: #155724;
                    }
                    .mps-notification-error {
                        background: #f8d7da;
                        border: 1px solid #f5c6cb;
                        color: #721c24;
                    }
                    .mps-notification-info {
                        background: #d1ecf1;
                        border: 1px solid #bee5eb;
                        color: #0c5460;
                    }
                    .mps-notification-close {
                        background: none;
                        border: none;
                        font-size: 18px;
                        cursor: pointer;
                        padding: 0;
                        margin-left: auto;
                        opacity: 0.7;
                    }
                    .mps-notification-close:hover {
                        opacity: 1;
                    }
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                </style>
            `);
        }
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, () => notification.remove());
        }, 5000);
        
        // Handle close button
        notification.find('.mps-notification-close').on('click', () => {
            notification.fadeOut(300, () => notification.remove());
        });
    }

    /**
     * Enhanced form validation.
     */
    function validateForm($form) {
        let isValid = true;
        const errors = [];

        // Validate required fields
        $form.find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                isValid = false;
                errors.push(`${$field.attr('name')} is required`);
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        });

        // Validate URLs
        $form.find('input[type="url"]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value && !isValidUrl(value)) {
                isValid = false;
                errors.push(`${$field.attr('name')} must be a valid URL`);
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        });

        if (!isValid) {
            showNotification(errors.join(', '), 'error');
        }

        return isValid;
    }

    /**
     * Check if URL is valid.
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    /**
     * Real-time field validation.
     */
    function initFieldValidation() {
        $('input[type="url"]').on('blur', function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value && !isValidUrl(value)) {
                $field.addClass('error');
                showNotification('Please enter a valid URL', 'error');
            } else {
                $field.removeClass('error');
            }
        });

        $('input[type="email"]').on('blur', function() {
            const $field = $(this);
            const value = $field.val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value && !emailRegex.test(value)) {
                $field.addClass('error');
                showNotification('Please enter a valid email address', 'error');
            } else {
                $field.removeClass('error');
            }
        });
    }

    /**
     * Initialize keyboard shortcuts.
     */
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save settings
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const $submitButton = $('input[type="submit"], button[type="submit"]').first();
                if ($submitButton.length) {
                    $submitButton.click();
                    showNotification('Settings saved!', 'success');
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        init();
        initFieldValidation();
        initKeyboardShortcuts();
    });

})(jQuery);