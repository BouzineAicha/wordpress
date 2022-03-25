<?php

if (!function_exists('smartslider3_admin_menu')) {
    if (!defined('NEXTEND_SMARTSLIDER_3_URL_PATH')) {
        define('NEXTEND_SMARTSLIDER_3_URL_PATH', 'smart-slider3');
    }

    add_action('admin_menu', 'smartslider3_admin_menu');

    function smartslider3_admin_menu() {
        add_menu_page('Smart Slider', 'Smart Slider', 'smartslider', NEXTEND_SMARTSLIDER_3_URL_PATH, 'smartslider3_admin_error', 'dashicons-format-gallery');
    }

    function smartslider3_admin_error() {
    }

    if (isset($_GET['page']) && $_GET['page'] == NEXTEND_SMARTSLIDER_3_URL_PATH) {
        if (!version_compare(PHP_VERSION, '7.0', '>=')) {

            wp_die(sprintf('<div class="error">%s</div>', wpautop(sprintf('Smart Slider 3 requires PHP version 7.0+, plugin is currently NOT RUNNING. Current PHP version: %1$s. %2$s%2$s Consult your host about %3$s upgrading your PHP version%4$s.', PHP_VERSION, '<br>', '<a href="https://wordpress.org/support/update-php/" target="_blank">', '</a>'))));
        } else if (!version_compare(get_bloginfo('version'), '4.9', '>=')) {

            wp_die(sprintf('<div class="error">%s</div>', wpautop('Smart Slider 3 requires WordPress version 4.9+. Because you are using an earlier version, the plugin is currently NOT RUNNING.')));
        }
    }
}


if (!function_exists('smartslider3_fail_php_version')) {
    function smartslider3_fail_php_version() {
        $html_message = sprintf('<div class="error">%s</div>', wpautop(sprintf('Smart Slider 3 requires PHP version 7.0+, plugin is currently NOT RUNNING. Current PHP version: %1$s. %2$s%2$s Consult your host about %3$s upgrading your PHP version%4$s.', PHP_VERSION, '<br>', '<a href="https://wordpress.org/support/update-php/" target="_blank">', '</a>')));
        echo wp_kses_post($html_message);
    }
}

if (!function_exists('smartslider3_fail_wp_version')) {
    function smartslider3_fail_wp_version() {
        $html_message = sprintf('<div class="error">%s</div>', wpautop('Smart Slider 3 requires WordPress version 4.9+. Because you are using an earlier version, the plugin is currently NOT RUNNING.'));
        echo wp_kses_post($html_message);
    }
}