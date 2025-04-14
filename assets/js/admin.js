jQuery(document).ready(function($) {
    // Handle individual plugin actions
    $(document).on('click', '.quickstart-plugin-action-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $card = $button.closest('.quickstart-plugin-card');
        const pluginSlug = $card.data('slug');
        const action = $button.data('action');
        
        $button.prop('disabled', true);
        
        if (action === 'install') {
            $button.text(quickstartPlugin.installing);
            installPlugin(pluginSlug, $button);
        } else if (action === 'activate') {
            $button.text(quickstartPlugin.activating);
            activatePlugin(pluginSlug, $button);
        }
    });
    
    // Handle bulk install all
    $('#quickstart-plugin-install-all').on('click', function(e) {
        e.preventDefault();
        bulkAction('install');
    });
    
    // Handle bulk activate all
    $('#quickstart-plugin-activate-all').on('click', function(e) {
        e.preventDefault();
        bulkAction('activate');
    });
    
    // Install plugin function
    function installPlugin(pluginSlug, $button) {
        $.ajax({
            url: quickstartPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'quickstart_plugin_install_plugin',
                slug: pluginSlug,
                nonce: quickstartPlugin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text(quickstartPlugin.activate);
                    $button.data('action', 'activate');
                    $button.removeClass('not-installed').addClass('installed');
                } else {
                    $button.text(quickstartPlugin.error);
                    console.error(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $button.text(quickstartPlugin.error);
                console.error(error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    
    // Activate plugin function
    function activatePlugin(pluginSlug, $button) {
        $.ajax({
            url: quickstartPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'quickstart_plugin_activate_plugin',
                slug: pluginSlug,
                nonce: quickstartPlugin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text(quickstartPlugin.active);
                    $button.data('action', 'deactivate');
                    $button.removeClass('installed').addClass('active');
                } else {
                    $button.text(quickstartPlugin.error);
                    console.error(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $button.text(quickstartPlugin.error);
                console.error(error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    
    // Bulk action function
    function bulkAction(actionType) {
        const $buttons = $('.quickstart-plugin-action-button');
        const $bulkButtons = $('#quickstart-plugin-install-all, #quickstart-plugin-activate-all');
        
        $buttons.prop('disabled', true);
        $bulkButtons.prop('disabled', true);
        
        $.ajax({
            url: quickstartPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'quickstart_plugin_bulk_action',
                action_type: actionType,
                nonce: quickstartPlugin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $.each(response.data.results, function(pluginSlug, result) {
                        const $card = $(`.quickstart-plugin-card[data-slug="${pluginSlug}"]`);
                        const $button = $card.find('.quickstart-plugin-action-button');
                        
                        if (result.success) {
                            if (result.status === 'installed') {
                                $button.text(quickstartPlugin.activate);
                                $button.data('action', 'activate');
                                $button.removeClass('not-installed').addClass('installed');
                            } else if (result.status === 'active') {
                                $button.text(quickstartPlugin.active);
                                $button.data('action', 'deactivate');
                                $button.removeClass('installed').addClass('active');
                            }
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
            },
            complete: function() {
                $buttons.prop('disabled', false);
                $bulkButtons.prop('disabled', false);
            }
        });
    }
});