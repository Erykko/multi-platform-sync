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
                } else {
                    $('#mps-sync-result').html('<div class="mps-error">' + response.data.message + '</div>');
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
                $('#mps-sync-result').html('<div class="mps-error">' + mps_admin_vars.strings.sync_error + '</div>');
                $('.mps-loader').hide();
                $('.mps-progress-message').hide();
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

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery); 