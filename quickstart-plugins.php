<?php
/**
 * Plugin Name: QuickStart Plugins
 * Plugin URI: https://github.com/Ajax-thebug/QuickStart-Plugins
 * Description: Automatically install your favorite WordPress plugins with a single click.
 * Version: 1.0.0
 * Author: Bhupendra Jaiswal
 * Author URI: https://thebigsparrow.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: quickstart-plugins
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class QuickStart_Plugins {
    
    // Default plugins to install
    private $default_plugins = array(
        'elementor' => array(
            'name' => 'Elementor',
            'slug' => 'elementor',
            'required' => true,
        ),
        'all-in-one-wp-migration' => array(
            'name' => 'All-in-One WP Migration',
            'slug' => 'all-in-one-wp-migration',
            'required' => true,
        ),
        'fluent-snippets' => array(
            'name' => 'Fluent Snippets',
            'slug' => 'fluent-snippets',
            'required' => true,
        ),
        'ewww-image-optimizer' => array(
            'name' => 'EWWW Image Optimizer',
            'slug' => 'ewww-image-optimizer',
            'required' => true,
        ),
    );
    
    // User added plugins
    private $user_plugins = array();
    
    // Combined list of all plugins
    private $all_plugins = array();
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add action for installing plugins
        add_action('admin_init', array($this, 'maybe_install_plugins'));
        
        // Enqueue admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Load saved user plugins
        $this->user_plugins = get_option('qsp_user_plugins', array());
        
        // Combine default and user plugins
        $this->all_plugins = array_merge($this->default_plugins, $this->user_plugins);
        
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('quickstart-plugins', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_quickstart-plugins' !== $hook) {
            return;
        }
        
        // Register and enqueue admin styles
        wp_register_style('quickstart-plugins-admin', false);
        wp_enqueue_style('quickstart-plugins-admin');
        
        // Add inline styles
        wp_add_inline_style('quickstart-plugins-admin', '
            .qsp-wrap {
                max-width: 900px;
            }
            .qsp-header {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .qsp-greeting {
                font-size: 1.5em;
                margin: 0;
                color: #23282d;
            }
            .qsp-greeting span {
                font-weight: bold;
                color: #2271b1;
            }
            .qsp-card {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .qsp-card h2 {
                margin-top: 0;
                padding-bottom: 12px;
                border-bottom: 1px solid #eee;
            }
            .qsp-form-group {
                margin-bottom: 15px;
            }
            .qsp-form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .qsp-form-row {
                display: flex;
                flex-wrap: wrap;
                margin-right: -10px;
                margin-left: -10px;
                align-items: flex-end;
            }
            .qsp-form-col {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
                padding-right: 10px;
                padding-left: 10px;
                box-sizing: border-box;
            }
            .qsp-plugin-list {
                margin-top: 20px;
            }
            .qsp-plugin-list table {
                border-collapse: collapse;
                width: 100%;
            }
            .qsp-plugin-list th,
            .qsp-plugin-list td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            .qsp-plugin-list th {
                background-color: #f8f9fa;
                font-weight: 600;
            }
            .qsp-plugin-list tr:hover {
                background-color: #f8f9fa;
            }
            .qsp-plugin-icon {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 3px;
                margin-right: 10px;
                vertical-align: middle;
            }
            .qsp-plugin-info {
                display: flex;
                align-items: center;
            }
            .qsp-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 5px;
            }
            .qsp-badge-default {
                background-color: #e7f5ff;
                color: #0c5460;
            }
            .qsp-badge-user {
                background-color: #f8f9fa;
                color: #495057;
            }
            .qsp-install-button {
                text-align: center;
                margin-top: 20px;
            }
            .qsp-description {
                color: #666;
                font-style: italic;
                margin-top: 5px;
                font-size: 13px;
            }
            @media (max-width: 768px) {
                .qsp-form-col {
                    flex: 0 0 100%;
                    max-width: 100%;
                    margin-bottom: 15px;
                }
            }
        ');
    }
    
    /**
     * Add options page to admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('QuickStart Plugins', 'quickstart-plugins'),
            __('QuickStart Plugins', 'quickstart-plugins'),
            'manage_options',
            'quickstart-plugins',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings for the plugin
     */
    public function register_settings() {
        register_setting('qsp_settings', 'qsp_user_plugins');
        register_setting('qsp_settings', 'qsp_auto_install');
        
        add_settings_section(
            'qsp_section_plugins',
            __('Plugin Management', 'quickstart-plugins'),
            array($this, 'section_plugins_callback'),
            'qsp_settings'
        );
        
        add_settings_field(
            'qsp_field_auto_install',
            __('Auto Install on Activation', 'quickstart-plugins'),
            array($this, 'field_auto_install_callback'),
            'qsp_settings',
            'qsp_section_plugins'
        );
        
        add_settings_field(
            'qsp_field_add_plugin',
            __('Add New Plugin', 'quickstart-plugins'),
            array($this, 'field_add_plugin_callback'),
            'qsp_settings',
            'qsp_section_plugins'
        );
    }
    
    /**
     * Section description callback
     */
    public function section_plugins_callback() {
        echo '<p>' . __('Manage your list of plugins to automatically install.', 'quickstart-plugins') . '</p>';
    }
    
    /**
     * Auto install field callback
     */
    public function field_auto_install_callback() {
        $auto_install = get_option('qsp_auto_install', false);
        echo '<label><input type="checkbox" name="qsp_auto_install" value="1" ' . checked(1, $auto_install, false) . '> ' . __('Enable automatic installation of plugins when QuickStart Plugins is activated', 'quickstart-plugins') . '</label>';
    }
    
    /**
     * Add plugin field callback
     */
    public function field_add_plugin_callback() {
        ?>
        <div class="qsp-form-row">
            <div class="qsp-form-col">
                <div class="qsp-form-group">
                    <label for="qsp-plugin-name"><?php _e('Plugin Name:', 'quickstart-plugins'); ?></label>
                    <input type="text" class="regular-text" id="qsp-plugin-name" name="qsp_new_plugin[name]" placeholder="<?php _e('Plugin Name', 'quickstart-plugins'); ?>">
                </div>
            </div>
            <div class="qsp-form-col">
                <div class="qsp-form-group">
                    <label for="qsp-plugin-slug"><?php _e('Plugin Slug:', 'quickstart-plugins'); ?></label>
                    <input type="text" class="regular-text" id="qsp-plugin-slug" name="qsp_new_plugin[slug]" placeholder="<?php _e('plugin-slug', 'quickstart-plugins'); ?>">
                    <p class="qsp-description"><?php _e('Found in wordpress.org/plugins/plugin-slug/', 'quickstart-plugins'); ?></p>
                </div>
            </div>
            <div class="qsp-form-col">
                <div class="qsp-form-group">
                    <button type="button" class="button button-primary" id="qsp-add-plugin"><?php _e('Add Plugin', 'quickstart-plugins'); ?></button>
                </div>
            </div>
        </div>
        
        <div class="qsp-plugin-list">
            <h3><?php _e('Current Plugin List', 'quickstart-plugins'); ?></h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Plugin', 'quickstart-plugins'); ?></th>
                        <th><?php _e('Slug', 'quickstart-plugins'); ?></th>
                        <th><?php _e('Type', 'quickstart-plugins'); ?></th>
                        <th><?php _e('Actions', 'quickstart-plugins'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->default_plugins as $plugin) : 
                        $plugin_icon = $this->get_plugin_icon($plugin['slug']);
                    ?>
                    <tr>
                        <td>
                            <div class="qsp-plugin-info">
                                <?php if ($plugin_icon) : ?>
                                <img src="<?php echo esc_url($plugin_icon); ?>" class="qsp-plugin-icon" alt="<?php echo esc_attr($plugin['name']); ?>">
                                <?php else : ?>
                                <div class="qsp-plugin-icon dashicons dashicons-admin-plugins"></div>
                                <?php endif; ?>
                                <?php echo esc_html($plugin['name']); ?>
                                <span class="qsp-badge qsp-badge-default"><?php _e('Default', 'quickstart-plugins'); ?></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($plugin['slug']); ?></td>
                        <td><?php _e('Default', 'quickstart-plugins'); ?></td>
                        <td>-</td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php foreach ($this->user_plugins as $key => $plugin) : 
                        $plugin_icon = $this->get_plugin_icon($plugin['slug']);
                    ?>
                    <tr>
                        <td>
                            <div class="qsp-plugin-info">
                                <?php if ($plugin_icon) : ?>
                                <img src="<?php echo esc_url($plugin_icon); ?>" class="qsp-plugin-icon" alt="<?php echo esc_attr($plugin['name']); ?>">
                                <?php else : ?>
                                <div class="qsp-plugin-icon dashicons dashicons-admin-plugins"></div>
                                <?php endif; ?>
                                <?php echo esc_html($plugin['name']); ?>
                                <span class="qsp-badge qsp-badge-user"><?php _e('Custom', 'quickstart-plugins'); ?></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($plugin['slug']); ?></td>
                        <td><?php _e('User Added', 'quickstart-plugins'); ?></td>
                        <td>
                            <a href="#" class="qsp-remove-plugin" data-key="<?php echo esc_attr($key); ?>"><?php _e('Remove', 'quickstart-plugins'); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add new plugin
            $('#qsp-add-plugin').on('click', function() {
                var name = $('#qsp-plugin-name').val();
                var slug = $('#qsp-plugin-slug').val();
                
                if (!name || !slug) {
                    alert('<?php _e('Please enter both name and slug.', 'quickstart-plugins'); ?>');
                    return;
                }
                
                var data = {
                    action: 'qsp_add_plugin',
                    name: name,
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('qsp_add_plugin'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Remove plugin
            $('.qsp-remove-plugin').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('<?php _e('Are you sure you want to remove this plugin?', 'quickstart-plugins'); ?>')) {
                    return;
                }
                
                var key = $(this).data('key');
                
                var data = {
                    action: 'qsp_remove_plugin',
                    key: key,
                    nonce: '<?php echo wp_create_nonce('qsp_remove_plugin'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get plugin icon URL
     */
    public function get_plugin_icon($slug) {
        if (empty($slug)) {
            return false;
        }
        
        // Create a transient key for caching
        $transient_key = 'qsp_plugin_icon_' . sanitize_key($slug);
        $icon_url = get_transient($transient_key);
        
        if (false === $icon_url) {
            // Get plugin info from API
            $api = plugins_api('plugin_information', array(
                'slug' => $slug,
                'fields' => array(
                    'icons' => true,
                    'short_description' => false,
                    'sections' => false,
                    'requires' => false,
                    'rating' => false,
                    'ratings' => false,
                    'downloaded' => false,
                    'last_updated' => false,
                    'added' => false,
                    'tags' => false,
                    'compatibility' => false,
                    'homepage' => false,
                    'donate_link' => false,
                ),
            ));
            
            if (!is_wp_error($api) && isset($api->icons)) {
                // Try to get the highest resolution icon
                if (isset($api->icons['2x'])) {
                    $icon_url = $api->icons['2x'];
                } elseif (isset($api->icons['1x'])) {
                    $icon_url = $api->icons['1x'];
                } elseif (isset($api->icons['default'])) {
                    $icon_url = $api->icons['default'];
                } else {
                    $icon_url = false;
                }
                
                // Cache the result for 1 day
                set_transient($transient_key, $icon_url, DAY_IN_SECONDS);
            } else {
                $icon_url = false;
                // Cache the failure for 1 hour to avoid hammering the API
                set_transient($transient_key, false, HOUR_IN_SECONDS);
            }
        }
        
        return $icon_url;
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current user's first name
        $current_user = wp_get_current_user();
        $first_name = $current_user->first_name;
        if (empty($first_name)) {
            $first_name = $current_user->display_name;
        }
        
        ?>
        <div class="wrap qsp-wrap">
            <div class="qsp-header">
                <h1 class="qsp-greeting"><?php _e('Hello', 'quickstart-plugins'); ?>, <span><?php echo esc_html($first_name); ?></span>!</h1>
                <img src="<?php echo esc_url(plugins_url('assets/quickstart-logo.png', __FILE__)); ?>" alt="QuickStart Plugins" width="150">
            </div>
            
            <?php if (isset($_GET['installed']) && $_GET['installed'] == 1) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Plugins have been installed successfully!', 'quickstart-plugins'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="qsp-card">
                <h2><?php _e('Plugin Settings', 'quickstart-plugins'); ?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('qsp_settings');
                    do_settings_sections('qsp_settings');
                    submit_button(__('Save Settings', 'quickstart-plugins'));
                    ?>
                </form>
            </div>
            
            <div class="qsp-card">
                <h2><?php _e('Install Plugins Now', 'quickstart-plugins'); ?></h2>
                <p><?php _e('Click the button below to install all plugins in the list that are not already installed.', 'quickstart-plugins'); ?></p>
                <div class="qsp-install-button">
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=quickstart-plugins&action=install'), 'qsp-install-plugins')); ?>" class="button button-primary button-hero"><?php _e('Install Plugins', 'quickstart-plugins'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if we should install plugins and handle the installation
     */
    public function maybe_install_plugins() {
        // Check if we're on the right page and have the right action
        if (isset($_GET['page']) && $_GET['page'] === 'quickstart-plugins' && 
            isset($_GET['action']) && $_GET['action'] === 'install' && 
            check_admin_referer('qsp-install-plugins')) {
            
            $this->install_plugins();
            
            // Redirect back to the settings page
            wp_redirect(admin_url('options-general.php?page=quickstart-plugins&installed=1'));
            exit;
        }
        
        // Auto-install on plugin activation if enabled
        if (get_option('qsp_auto_install', false) && get_transient('qsp_activated')) {
            delete_transient('qsp_activated');
            $this->install_plugins();
        }
    }
    
    /**
     * Install all plugins in the list
     */
    public function install_plugins() {
        if (!current_user_can('install_plugins')) {
            return;
        }
        
        // Include necessary files for plugin installation
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        
        $installer = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        
        foreach ($this->all_plugins as $plugin) {
            // Check if plugin is already installed
            $installed_plugins = get_plugins();
            $plugin_installed = false;
            
            foreach ($installed_plugins as $plugin_path => $plugin_data) {
                $plugin_file = explode('/', $plugin_path)[0];
                if ($plugin_file === $plugin['slug']) {
                    $plugin_installed = true;
                    break;
                }
            }
            
            // Skip if already installed
            if ($plugin_installed) {
                continue;
            }
            
            // Get plugin info
            $api = plugins_api('plugin_information', array(
                'slug' => $plugin['slug'],
                'fields' => array(
                    'short_description' => false,
                    'sections' => false,
                    'requires' => false,
                    'rating' => false,
                    'ratings' => false,
                    'downloaded' => false,
                    'last_updated' => false,
                    'added' => false,
                    'tags' => false,
                    'compatibility' => false,
                    'homepage' => false,
                    'donate_link' => false,
                ),
            ));
            
            if (is_wp_error($api)) {
                continue;
            }
            
            // Install the plugin
            $installer->install($api->download_link);
            
            // Activate the plugin
            $plugin_file = $installer->plugin_info();
            if ($plugin_file) {
                activate_plugin($plugin_file);
            }
        }
    }
    
    /**
     * AJAX handler for adding a plugin
     */
    public static function ajax_add_plugin() {
        check_ajax_referer('qsp_add_plugin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'quickstart-plugins')));
        }
        
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_text_field($_POST['slug']);
        
        if (empty($name) || empty($slug)) {
            wp_send_json_error(array('message' => __('Name and slug are required.', 'quickstart-plugins')));
        }
        
        $user_plugins = get_option('qsp_user_plugins', array());
        
        $user_plugins[$slug] = array(
            'name' => $name,
            'slug' => $slug,
            'required' => true,
        );
        
        update_option('qsp_user_plugins', $user_plugins);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for removing a plugin
     */
    public static function ajax_remove_plugin() {
        check_ajax_referer('qsp_remove_plugin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'quickstart-plugins')));
        }
        
        $key = sanitize_text_field($_POST['key']);
        
        if (empty($key)) {
            wp_send_json_error(array('message' => __('Invalid plugin key.', 'quickstart-plugins')));
        }
        
        $user_plugins = get_option('qsp_user_plugins', array());
        
        if (isset($user_plugins[$key])) {
            unset($user_plugins[$key]);
            update_option('qsp_user_plugins', $user_plugins);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Plugin not found.', 'quickstart-plugins')));
        }
    }
}

// Initialize the plugin
$quickstart_plugins = new QuickStart_Plugins();

// Register activation hook
register_activation_hook(__FILE__, 'qsp_activate');
function qsp_activate() {
    set_transient('qsp_activated', true, 30);
    
    // Create plugin assets directory
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
}

// AJAX handlers
add_action('wp_ajax_qsp_add_plugin', array('QuickStart_Plugins', 'ajax_add_plugin'));
add_action('wp_ajax_qsp_remove_plugin', array('QuickStart_Plugins', 'ajax_remove_plugin'));

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'qsp_action_links');
function qsp_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=quickstart-plugins') . '">' . __('Settings', 'quickstart-plugins') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add plugin meta links
add_filter('plugin_row_meta', 'qsp_plugin_meta', 10, 2);
function qsp_plugin_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $links[] = '<a href="https://yourwebsite.com/quickstart-plugins-docs" target="_blank">' . __('Documentation', 'quickstart-plugins') . '</a>';
        $links[] = '<a href="https://wordpress.org/support/plugin/quickstart-plugins" target="_blank">' . __('Support', 'quickstart-plugins') . '</a>';
    }
    return $links;
}
