<?php
/**
 * Plugin Name: QuickStart Plugin
 * Plugin URI: https://github.com/Ajax-thebug/QuickStart-Plugins
 * Description: Install your favorite WordPress plugins with one click. No more repetitive plugin installations!
 * Version: 1.0.1
 * Author: Bhupendra Jaiswal
 * Author URI: https://thebigsparrow.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: quickstart-plugin
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('QUICKSTART_PLUGIN_VERSION', '1.0.1');
define('QUICKSTART_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUICKSTART_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Default plugins to install
 */
function quickstart_plugin_default_plugins() {
    return array(
        'elementor' => array(
            'name' => 'Elementor',
            'slug' => 'elementor',
            'required' => true,
            'icon' => 'https://ps.w.org/elementor/assets/icon-128x128.png'
        ),
        'ewww-image-optimizer' => array(
            'name' => 'EWWW Image Optimizer',
            'slug' => 'ewww-image-optimizer',
            'required' => true,
            'icon' => 'https://ps.w.org/ewww-image-optimizer/assets/icon-128x128.png'
        ),
        'easy-code-manager' => array(
            'name' => 'Easy Code Manager',
            'slug' => 'easy-code-manager',
            'required' => true,
            'icon' => 'https://ps.w.org/easy-code-manager/assets/icon-128x128.png'
        ),
        'all-in-one-wp-migration' => array(
            'name' => 'All-in-One WP Migration',
            'slug' => 'all-in-one-wp-migration',
            'required' => true,
            'icon' => 'https://ps.w.org/all-in-one-wp-migration/assets/icon-128x128.png'
        )
    );
}

/**
 * Load plugin textdomain
 */
function quickstart_plugin_load_textdomain() {
    load_plugin_textdomain('quickstart-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'quickstart_plugin_load_textdomain');

/**
 * Enqueue admin scripts and styles
 */
function quickstart_plugin_admin_enqueue($hook) {
    if ('tools_page_quickstart-plugin' !== $hook && 'tools_page_quickstart-plugin-settings' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'quickstart-plugin-admin',
        QUICKSTART_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        QUICKSTART_PLUGIN_VERSION
    );

    wp_enqueue_script(
        'quickstart-plugin-admin',
        QUICKSTART_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery', 'updates'),
        QUICKSTART_PLUGIN_VERSION,
        true
    );

    wp_localize_script('quickstart-plugin-admin', 'quickstartPlugin', array(
        'installing' => __('Installing', 'quickstart-plugin'),
        'activating' => __('Activating', 'quickstart-plugin'),
        'active' => __('Active', 'quickstart-plugin'),
        'error' => __('Error', 'quickstart-plugin'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('quickstart-plugin-nonce')
    ));
}
add_action('admin_enqueue_scripts', 'quickstart_plugin_admin_enqueue');

/**
 * Add admin menu
 */
function quickstart_plugin_admin_menu() {
    add_submenu_page(
        'tools.php',
        __('QuickStart Plugin', 'quickstart-plugin'),
        __('QuickStart', 'quickstart-plugin'),
        'install_plugins',
        'quickstart-plugin',
        'quickstart_plugin_admin_page'
    );

    add_submenu_page(
        null,
        __('QuickStart Plugin Settings', 'quickstart-plugin'),
        '',
        'manage_options',
        'quickstart-plugin-settings',
        'quickstart_plugin_settings_page'
    );
}
add_action('admin_menu', 'quickstart_plugin_admin_menu');

/**
 * Get all plugins including custom ones from options
 */
function quickstart_plugin_get_all_plugins() {
    $default_plugins = quickstart_plugin_default_plugins();
    $custom_plugins = get_option('quickstart_custom_plugins', array());
    
    return array_merge($default_plugins, $custom_plugins);
}

/**
 * Admin page content
 */
function quickstart_plugin_admin_page() {
    $plugins = quickstart_plugin_get_all_plugins();
    ?>
    <div class="quickstart-plugin-wrapper">
        <header class="quickstart-plugin-header">
            <h1><?php _e('Hello', 'quickstart-plugin'); ?></h1>
            <p><?php _e('Install your favorite plugins with one click', 'quickstart-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('tools.php?page=quickstart-plugin-settings')); ?>" class="button button-secondary"><?php _e('Add Custom Plugins', 'quickstart-plugin'); ?></a>
        </header>

        <div class="quickstart-plugin-grid">
            <?php foreach ($plugins as $key => $plugin): 
                $plugin_status = quickstart_plugin_get_plugin_status($plugin['slug']);
                ?>
                <div class="quickstart-plugin-card" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                    <div class="quickstart-plugin-card-header">
                        <?php if (!empty($plugin['icon'])): ?>
                            <img src="<?php echo esc_url($plugin['icon']); ?>" alt="<?php echo esc_attr($plugin['name']); ?>" class="quickstart-plugin-icon">
                        <?php else: ?>
                            <div class="quickstart-plugin-icon-placeholder"></div>
                        <?php endif; ?>
                        <h3><?php echo esc_html($plugin['name']); ?></h3>
                    </div>
                    <div class="quickstart-plugin-card-actions">
                        <button class="button quickstart-plugin-action-button <?php echo esc_attr($plugin_status); ?>" 
                                data-action="<?php echo $plugin_status === 'not-installed' ? 'install' : ($plugin_status === 'installed' ? 'activate' : 'deactivate'); ?>">
                            <?php 
                            if ($plugin_status === 'active') {
                                _e('Active', 'quickstart-plugin');
                            } elseif ($plugin_status === 'installed') {
                                _e('Activate', 'quickstart-plugin');
                            } else {
                                _e('Install', 'quickstart-plugin');
                            }
                            ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="quickstart-plugin-bulk-actions">
            <button id="quickstart-plugin-install-all" class="button button-primary"><?php _e('Install All', 'quickstart-plugin'); ?></button>
            <button id="quickstart-plugin-activate-all" class="button button-primary"><?php _e('Activate All', 'quickstart-plugin'); ?></button>
        </div>

        <footer class="quickstart-plugin-footer">
            <p><?php echo __('Crafted with ♥ by', 'quickstart-plugin') . ' Bhupendra Jaiswal'; ?></p>
        </footer>
    </div>
    <?php
}

/**
 * Settings page content
 */
function quickstart_plugin_settings_page() {
    if (isset($_POST['submit_custom_plugin']) && check_admin_referer('quickstart_add_custom_plugin')) {
        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        $plugin_name = sanitize_text_field($_POST['plugin_name']);
        
        if (!empty($plugin_slug) && !empty($plugin_name)) {
            $custom_plugins = get_option('quickstart_custom_plugins', array());
            
            $custom_plugins[$plugin_slug] = array(
                'name' => $plugin_name,
                'slug' => $plugin_slug,
                'required' => false,
                'icon' => isset($_POST['plugin_icon']) ? esc_url_raw($_POST['plugin_icon']) : ''
            );
            
            update_option('quickstart_custom_plugins', $custom_plugins);
            
            echo '<div class="notice notice-success"><p>' . __('Plugin added successfully!', 'quickstart-plugin') . '</p></div>';
        }
    }
    
    if (isset($_GET['delete_plugin']) && check_admin_referer('quickstart_delete_custom_plugin')) {
        $plugin_slug = sanitize_text_field($_GET['delete_plugin']);
        $custom_plugins = get_option('quickstart_custom_plugins', array());
        
        if (isset($custom_plugins[$plugin_slug])) {
            unset($custom_plugins[$plugin_slug]);
            update_option('quickstart_custom_plugins', $custom_plugins);
            
            echo '<div class="notice notice-success"><p>' . __('Plugin removed successfully!', 'quickstart-plugin') . '</p></div>';
        }
    }
    
    $custom_plugins = get_option('quickstart_custom_plugins', array());
    ?>
    <div class="wrap quickstart-plugin-settings">
        <h1><?php _e('QuickStart Plugin Settings', 'quickstart-plugin'); ?></h1>
        
        <h2><?php _e('Add Custom Plugin', 'quickstart-plugin'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('quickstart_add_custom_plugin'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="plugin_slug"><?php _e('Plugin Slug', 'quickstart-plugin'); ?></label></th>
                    <td>
                        <input type="text" name="plugin_slug" id="plugin_slug" class="regular-text" required>
                        <p class="description"><?php _e('The WordPress.org plugin repository slug (e.g., "easy-code-manager")', 'quickstart-plugin'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="plugin_name"><?php _e('Plugin Name', 'quickstart-plugin'); ?></label></th>
                    <td>
                        <input type="text" name="plugin_name" id="plugin_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="plugin_icon"><?php _e('Icon URL (optional)', 'quickstart-plugin'); ?></label></th>
                    <td>
                        <input type="url" name="plugin_icon" id="plugin_icon" class="regular-text">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_custom_plugin" class="button button-primary" value="<?php _e('Add Plugin', 'quickstart-plugin'); ?>">
            </p>
        </form>
        
        <h2><?php _e('Custom Plugins', 'quickstart-plugin'); ?></h2>
        <?php if (empty($custom_plugins)): ?>
            <p><?php _e('No custom plugins added yet.', 'quickstart-plugin'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'quickstart-plugin'); ?></th>
                        <th><?php _e('Slug', 'quickstart-plugin'); ?></th>
                        <th><?php _e('Actions', 'quickstart-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_plugins as $slug => $plugin): ?>
                        <tr>
                            <td><?php echo esc_html($plugin['name']); ?></td>
                            <td><?php echo esc_html($slug); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('tools.php?page=quickstart-plugin-settings&delete_plugin=' . $slug), 'quickstart_delete_custom_plugin')); ?>" class="button button-secondary"><?php _e('Remove', 'quickstart-plugin'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Get plugin status
 */
function quickstart_plugin_get_plugin_status($plugin_slug) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    $plugin_path = quickstart_plugin_get_plugin_path($plugin_slug);

    if (isset($plugins[$plugin_path])) {
        return is_plugin_active($plugin_path) ? 'active' : 'installed';
    }

    return 'not-installed';
}

/**
 * Get plugin path from slug
 */
function quickstart_plugin_get_plugin_path($plugin_slug) {
    $plugins = get_plugins();
    foreach ($plugins as $plugin_path => $plugin_info) {
        if (strpos($plugin_path, $plugin_slug . '/') === 0) {
            return $plugin_path;
        }
    }
    return $plugin_slug . '/' . $plugin_slug . '.php';
}

/**
 * AJAX handler for plugin installation
 */
function quickstart_plugin_install_plugin() {
    if (!current_user_can('install_plugins') || !check_ajax_referer('quickstart-plugin-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Unauthorized', 'quickstart-plugin')));
    }

    $plugin_slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
    $plugins = quickstart_plugin_get_all_plugins();
    $plugin = isset($plugins[$plugin_slug]) ? $plugins[$plugin_slug] : false;

    if (!$plugin) {
        wp_send_json_error(array('message' => __('Plugin not found', 'quickstart-plugin')));
    }

    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $api = plugins_api('plugin_information', array(
        'slug' => $plugin_slug,
        'fields' => array(
            'sections' => false
        )
    ));

    if (is_wp_error($api)) {
        wp_send_json_error(array('message' => $api->get_error_message()));
    }

    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $result = $upgrader->install($api->download_link);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array(
        'message' => __('Plugin installed successfully', 'quickstart-plugin'),
        'status' => 'installed'
    ));
}
add_action('wp_ajax_quickstart_plugin_install_plugin', 'quickstart_plugin_install_plugin');

/**
 * AJAX handler for plugin activation
 */
function quickstart_plugin_activate_plugin() {
    if (!current_user_can('activate_plugins') || !check_ajax_referer('quickstart-plugin-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Unauthorized', 'quickstart-plugin')));
    }

    $plugin_slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
    $plugin_path = quickstart_plugin_get_plugin_path($plugin_slug);

    if (empty($plugin_path)) {
        wp_send_json_error(array('message' => __('Plugin not found', 'quickstart-plugin')));
    }

    $result = activate_plugin($plugin_path);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array(
        'message' => __('Plugin activated successfully', 'quickstart-plugin'),
        'status' => 'active'
    ));
}
add_action('wp_ajax_quickstart_plugin_activate_plugin', 'quickstart_plugin_activate_plugin');

/**
 * AJAX handler for bulk actions
 */
function quickstart_plugin_bulk_action() {
    if (!current_user_can('install_plugins') || !check_ajax_referer('quickstart-plugin-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Unauthorized', 'quickstart-plugin')));
    }

    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $plugins = quickstart_plugin_get_all_plugins();
    $results = array();

    foreach ($plugins as $plugin_slug => $plugin) {
        $current_status = quickstart_plugin_get_plugin_status($plugin_slug);
        
        if ($action === 'install' && $current_status === 'not-installed') {
            $response = quickstart_plugin_install_plugin_helper($plugin_slug);
            $results[$plugin_slug] = $response;
        } elseif ($action === 'activate' && ($current_status === 'installed' || $current_status === 'not-installed')) {
            $response = quickstart_plugin_activate_plugin_helper($plugin_slug);
            $results[$plugin_slug] = $response;
        } else {
            $results[$plugin_slug] = array(
                'success' => true,
                'message' => __('No action needed', 'quickstart-plugin'),
                'status' => $current_status
            );
        }
    }

    wp_send_json_success(array(
        'message' => __('Bulk action completed', 'quickstart-plugin'),
        'results' => $results
    ));
}
add_action('wp_ajax_quickstart_plugin_bulk_action', 'quickstart_plugin_bulk_action');

/**
 * Helper function for plugin installation
 */
function quickstart_plugin_install_plugin_helper($plugin_slug) {
    $plugins = quickstart_plugin_get_all_plugins();
    $plugin = isset($plugins[$plugin_slug]) ? $plugins[$plugin_slug] : false;

    if (!$plugin) {
        return array(
            'success' => false,
            'message' => __('Plugin not found', 'quickstart-plugin')
        );
    }

    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $api = plugins_api('plugin_information', array(
        'slug' => $plugin_slug,
        'fields' => array(
            'sections' => false
        )
    ));

    if (is_wp_error($api)) {
        return array(
            'success' => false,
            'message' => $api->get_error_message()
        );
    }

    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $result = $upgrader->install($api->download_link);

    if (is_wp_error($result)) {
        return array(
            'success' => false,
            'message' => $result->get_error_message()
        );
    }

    return array(
        'success' => true,
        'message' => __('Plugin installed successfully', 'quickstart-plugin'),
        'status' => 'installed'
    );
}

/**
 * Helper function for plugin activation
 */
function quickstart_plugin_activate_plugin_helper($plugin_slug) {
    $plugin_path = quickstart_plugin_get_plugin_path($plugin_slug);

    if (empty($plugin_path)) {
        return array(
            'success' => false,
            'message' => __('Plugin not found', 'quickstart-plugin')
        );
    }

    $result = activate_plugin($plugin_path);

    if (is_wp_error($result)) {
        return array(
            'success' => false,
            'message' => $result->get_error_message()
        );
    }

    return array(
        'success' => true,
        'message' => __('Plugin activated successfully', 'quickstart-plugin'),
        'status' => 'active'
    );
}

/**
 * Add settings link to plugin actions
 */
function quickstart_plugin_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('tools.php?page=quickstart-plugin-settings') . '">' . __('Settings', 'quickstart-plugin') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'quickstart_plugin_add_action_links');